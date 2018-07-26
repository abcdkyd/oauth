<?php
/**
 * Created by PhpStorm.
 * User: vin
 * Date: 2018/7/9
 * Time: 上午9:16
 */

namespace App\Http\Controllers\Clients;


use App\Eloquent\OauthClient;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Server\ResourceServer;
use App\Eloquent\MemberMeta;
use Zend\Diactoros\Stream;


class ClientsController extends BaseController
{
    protected $server;

    public function __construct(ResourceServer $server)
    {
        $this->server = $server;
    }

    public function redirect(Request $request)
    {
        Log::info('==========oauth跳转授权页面 start==========');
//        dd(base64_encode(openssl_encrypt(5, 'AES-256-ECB', config('aes-key'))));
        $request_data = $request->all();

        Log::info('oauth跳转授权页面接收参数：' . json_encode($request_data));

        $validator = validator($request_data, [
            'response_type' => 'required',
            'client_id' => 'required',
            'redirect_uri' => 'required',
            'scope' => 'required',
        ], [
            'response_type.required' => '缺少参数response_type',
            'client_id.required' => '缺少参数client_id',
            'redirect_uri.required' => '缺少参数redirect_uri',
            'scope.required' => '缺少参数scope',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
                'errorCode' => '100000',
                'message' => $message[0]
            ]);
        }

        return view('vendor.passport.prepare_authorize');
    }

    public function authorize(Request $request)
    {
        Log::info('==========oauth获取token start==========');
        $request_data = $request->all();

        Log::info('oauth获取token接收参数：' . json_encode($request_data));

        $validator = validator($request_data, [
            'client_id' => 'required',
            'client_secret' => 'required',
            'code' => 'required',
            'grant_type' => 'required',
        ], [
            'client_id.required' => '缺少参数client_id',
            'client_secret.required' => '缺少参数client_secret',
            'code.required' => '缺少参数code',
            'grant_type.required' => '缺少参数grant_type',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
                'errorCode' => '100000',
                'message' => $message[0],
            ]);
        }

        if ($request_data['grant_type'] !== 'authorization_code') {
            Log::error('参数grant_type的值有误：' . $request_data['grant_type']);
            return response()->json([
                'errorCode' => '100000',
                'message' => '参数grant_type的值需为authorization_code',
            ]);
        }

        $client_id = openssl_decrypt(base64_decode($request_data['client_id']), 'AES-256-ECB', config('aes-key'));

        if (!$client_id) {
            Log::error('不支持普惠通平台的Client_id[' . json_encode($request_data) . ']');
            return response()->json([
                'errorCode' => '400001',
                'message' => '不支持普惠通平台的Client_id',
            ]);
        }

        $client = OauthClient::query()
            ->select(['id', 'name', 'redirect'])
            ->where([
                'id' => $client_id,
                'secret' => $request_data['client_secret']
            ])
            ->first();

        if (!$client) {
            Log::error('不支持普惠通平台的Client_id[' . json_encode($request_data) . ']');
            return response()->json([
                'errorCode' => '400001',
                'message' => '不支持普惠通平台的Client_id'
            ]);
        }

        try {

            $http = new Client();

            $token_response = $http->post(url('/oauth/token'), [
                'form_params' => [
                    'grant_type' => $request_data['grant_type'],
                    'client_id' => $client_id,
                    'client_secret' => $request_data['client_secret'],
                    'code' => $request_data['code'],
                    'redirect_uri' => $client->redirect,
                ]
            ]);

            $result = json_decode((string)$token_response->getBody(), true);
            Log::debug($result);

            $params = [
                'client_id' => $request_data['client_id'],
                'secret' => $request_data['client_secret'],
            ];

            $openid_response = $http->request('GET', url('api/clients/user/openid') . '?' . http_build_query($params)
                , [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $result['access_token'],
                    ]
                ]);

            $openid_response_data = json_decode((string)$openid_response->getBody(), true);

            if ($openid_response_data['errorCode'] !== '000000') {
                Log::error('验证失败(' . $request_data['client_id'] . ')['
                    . json_encode($request_data) . ']->>' . json_encode($openid_response_data));
                return response()->json([
                    'errorCode' => '400000',
                    'message' => '验证失败，请重新获取验证[' . $openid_response_data['errorCode'] . ']',
                ]);
            }

            Log::debug('openid:' . $openid_response_data['openid']);

            return [
                'access_token' => $result['access_token'],
                'expires_in' => $result['expires_in'],
                'refresh_token' => $result['refresh_token'],
                'open_id' => $openid_response_data['openid']
            ];
        } catch (\Exception $e) {
            Log::error('获取token异常：[' . $e->getLine() . ']' . $e->getMessage());
            Log::debug('获取token异常：[' . json_encode($request_data) . ']');
            return response()->json([
                'errorCode' => '440002',
                'message' => '验证失败，请检查code是否有效'
            ]);
        }
    }

    public function prepareAuthorize(Request $request)
    {
        Log::info('==========oauth获取授权 start==========');

        $request_data = $request->all();

        Log::info('oauth获取授权接收参数：' . json_encode($request_data));

        $validator = validator($request_data, [
            'response_type' => 'required',
            'client_id' => 'required',
            'redirect_uri' => 'required',
            'scope' => 'required',
        ], [
            'response_type.required' => '缺少参数response_type',
            'client_id.required' => '缺少参数client_id',
            'redirect_uri.required' => '缺少参数redirect_uri',
            'scope.required' => '缺少参数scope',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
                'errorCode' => '100000',
                'message' => $message[0]
            ]);
        }

        if (isset($request_data['stat']) && !empty($request_data['stat'])) {

            $stat = Auth::guard('jwt')->setToken($request_data['stat'])->payload();
            $payload = $stat->toArray();

            if (isset($payload['sub']) && !empty($payload['sub'])) {
                Auth::guard('web')->loginUsingId($payload['sub']);
            }
        }

        $client_id = openssl_decrypt(base64_decode($request_data['client_id']), 'AES-256-ECB', config('aes-key'));

        if (!$client_id) {
            Log::error('不支持普惠通平台的Client_id[' . json_encode($request_data) . ']');
            return response()->json([
                'errorCode' => '400001',
                'message' => '不支持普惠通平台的Client_id'
            ]);
        }

        $client = OauthClient::query()
            ->select(['id', 'name', 'redirect'])
            ->where('id', $client_id)
            ->first();

        if (!$client) {
            Log::error('不支持普惠通平台的Client_id[' . json_encode($request_data) . ']');
            return response()->json([
                'errorCode' => '400001',
                'message' => '不支持普惠通平台的Client_id'
            ]);
        }

        if ($request_data['redirect_uri'] !== $client->redirect) {
            Log::error("[{$request_data['client_id']}]redirect_uri域名与后台配置不一致:({$request_data['redirect_uri']})");
            return response()->json([
                'errorCode' => '400002',
                'message' => 'redirect_uri域名与后台配置不一致'
            ]);
        }

        $query = http_build_query([
            'client_id' => $client_id,
            'redirect_uri' => $request['redirect_uri'],
            'response_type' => 'code',
            'scope' => $request_data['scope'],
        ]);
//dd( app('router')->getRoutes());
        return redirect(url('oauth/authorize') . '?' . $query);
    }

    public function refreshToken(Request $request)
    {
        Log::info('==========oauth刷新token start==========');

        $request_data = $request->all();

        Log::info('oauth刷新token接收参数：' . json_encode($request_data));

        $validator = validator($request_data, [
            'grant_type' => 'required',
            'refresh_token' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
        ], [
            'grant_type.required' => '缺少参数grant_type',
            'refresh_token.required' => '缺少参数refresh_token',
            'client_id.required' => '缺少参数client_id',
            'client_secret.required' => '缺少参数client_secret',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
                'errorCode' => '100000',
                'message' => $message[0]
            ]);
        }

        $client_id = openssl_decrypt(base64_decode($request_data['client_id']), 'AES-256-ECB', config('aes-key'));

        if (!$client_id) {
            Log::error('不支持普惠通平台的Client_id[' . json_encode($request_data) . ']');
            return response()->json([
                'errorCode' => '400001',
                'message' => '不支持普惠通平台的Client_id',
            ]);
        }

        $client = OauthClient::query()
            ->select(['id', 'name', 'redirect'])
            ->where([
                'id' => $client_id,
                'secret' => $request_data['client_secret']
            ])
            ->first();

        if (!$client) {
            Log::error('不支持普惠通平台的Client_id[' . json_encode($request_data) . ']');
            return response()->json([
                'errorCode' => '400001',
                'message' => '不支持普惠通平台的Client_id'
            ]);
        }

        try {
            $http = new Client();

            $response = $http->post(url('/oauth/token'), [
                'form_params' => [
                    'grant_type' => $request_data['grant_type'],
                    'refresh_token' => $request_data['refresh_token'],
                    'client_id' => $client_id,
                    'client_secret' => $request_data['client_secret'],
                    'scope' => '',
                ],
            ]);

            $result = json_decode((string)$response->getBody(), true);
            Log::debug($result);

            return [
                'errorCode' => '000000',
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'expires_in' => $result['expires_in'],
            ];

        } catch (\Exception $e) {
            Log::error('获取token异常：[' . $e->getLine() . ']' . $e->getMessage());
            Log::debug('获取token异常：refresh_token[' . $request_data['refresh_token'] . ']');
            return [
                'errorCode' => '440003',
                'message' => '刷新access_token失败',
            ];
        }
    }

    public function callbackUnionpay(Request $request)
    {
        Log::info('==========oauth银联回调 start==========');
        $input = new Stream('php://input');

        Log::info('oauth银联回调接收输入流：' . $input->getContents());

        $request_data = $request->all();

        Log::info('oauth银联回调接收参数：' . json_encode($request_data));

        $validator = validator($request_data, [
            'open_id' => 'required',
            'service_type' => 'required',
            'trans_status' => 'required',
            'trans_id' => 'required',
            'trans_time' => 'required',
        ], [
            'open_id.required' => '缺少参数open_id',
            'service_type.required' => '缺少参数service_type',
            'trans_status.required' => '缺少参数trans_status',
            'trans_id.required' => '缺少参数trans_id',
            'trans_time.required' => '缺少参数trans_time',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
                'errorCode' => '100000',
                'message' => $message[0]
            ]);
        }

        $member_meta = MemberMeta::query()
            ->where([
                'meta_value' => $request_data['open_id'],
            ])->where('meta_key', 'like', '%\_openid')
            ->first();

        if (!$member_meta || empty($member_meta->meta_key)) {
            Log::error('该openid不存在[' . json_encode($request_data) . ']');
            return response()->json([
                'errorCode' => '400006',
                'message' => '该openid不存在'
            ]);
        }

        $user_id = $member_meta->member_id;

        $request_url = config('oauth.debug') ?
            config('oauth.debug_conf.callback_unionpay_api_server')
            : config('oauth.production_conf.callback_unionpay_api_server');


        $request_data['describe'] = $request->input('describe', '');

        $encryptArr = [
            'user_id' => $user_id,
            'timestamp' => date('YmdHis'),
            'callback_params' => json_encode($request_data),
        ];

        $encryptStr = '';

        foreach ($encryptArr as $value) {
            $encryptStr .= $value;
        }

        $token = base64_encode(md5("jhpm_unionpay@{$encryptStr}", true));
        $encryptArr['token'] = $token;

        try {
            $http = new Client();

            $response = $http->post($request_url, [
                'form_params' => $encryptArr,
            ]);

            return json_decode((string)$response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('回调失败[' . json_encode($response) . ']');
            return response()->json([
                'errorCode' => '400006',
                'message' => '回调失败，'
            ]);
        }
    }
}