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


class ClientsController extends BaseController
{
    protected $server;

    public function __construct(ResourceServer $server)
    {
        $this->server = $server;
    }

    public function redirect(Request $request)
    {
//        dd(base64_encode(openssl_encrypt(4, 'AES-256-ECB', config('aes-key'))));
        $request_data = $request->all();

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
        $request_data = $request->all();

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

        $client_id = openssl_decrypt(base64_decode($request_data['client_id']), 'AES-256-ECB', config('aes-key'));

        if (!$client_id) {
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
                    'client_id' => $client_id,
                    'client_secret' => $request_data['client_secret'],
                    'code' => $request_data['code'],
                    'redirect_uri' => $client->redirect,
                ]
            ]);

            $result = json_decode((string)$response->getBody(), true);

            $params = [
                'client_id' => $request_data['client_id'],
                'secret' => $request_data['client_secret'],
            ];

            $response = $http->request('GET', url('api/clients/user/openid') . '?' . http_build_query($params)
                , [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $result['access_token'],
                    ]
                ]);

            $response_data = json_decode((string)$response->getBody(), true);

            if ($response_data['errorCode'] !== '000000') {
                return response()->json([
                    'errorCode' => '400000',
                    'message' => '验证失败，请重新获取验证[' . $response_data['errorCode'] . ']',
                ]);
            }

            return [
                'access_token' => $result['access_token'],
                'expires_in' => $result['expires_in'],
                'refresh_token' => $result['refresh_token'],
                'open_id' => $response_data['openid']
            ];
        } catch (\Exception $e) {
            Log::error('获取token异常：' . $e->getMessage());
            return response()->json([
                'errorCode' => '440002',
                'message' => '验证失败，请检查code是否有效'
            ]);
        }
    }

    public function prepareAuthorize(Request $request)
    {
        $request_data = $request->all();

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
            return response()->json([
                'errorCode' => '400001',
                'message' => '不支持普惠通平台的Client_id'
            ]);
        }

        if ($request_data['redirect_uri'] !== $client->redirect) {
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

        return redirect(url('oauth/authorize') . '?' . $query);
    }

    public function refreshToken(Request $request)
    {
        $request_data = $request->all();

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

            return [
                'errorCode' => '000000',
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
                'expires_in' => $result['expires_in'],
            ];

        } catch (\Exception $e) {
            return [
                'errorCode' => '440003',
                'message' => '刷新access_token失败',
            ];
        }
    }

    public function callbackUnionpay(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'errorCode' => '200000',
                'message' => '该用户不存在'
            ]);
        }

        $request_data = $request->all();

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

        $channel = MemberMeta::query()->where([
            'meta_value' => $request_data['open_id'],
            'member_id' => $user->id,
        ])->where('meta_key', 'like', '%\_openid')
            ->value('meta_key');

        if (!$channel) {
            return response()->json([
                'errorCode' => '400006',
                'message' => '该openid不存在'
            ]);
        }


        $request_url = config('oauth.debug') ?
            config('oauth.debug_conf.callback_unionpay_api_server')
            : config('oauth.production_conf.callback_unionpay_api_server');


        $encryptArr = [
            'user_id' => $user->id,
            'timestamp' => date('YmdHis'),
            'callback_params' => json_encode($request_data),
        ];

        $encryptStr = '';

        foreach ($encryptArr as $value) {
            $encryptStr .= $value;
        }

        $token = base64_encode(md5("jhpm_unionpay@{$encryptStr}", true));
        $encryptArr['token'] = $token;

        $http = new Client();

        $response = $http->post($request_url, [
            'form_params' => $encryptArr,
        ]);

        return json_decode((string)$response->getBody(), true);
    }
}