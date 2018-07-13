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
use League\OAuth2\Server\ResourceServer;


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
            'appid' => 'required',
            'redirect_uri' => 'required',
            'scope' => 'required',
        ], [
            'response_type.required' => '缺少参数response_type',
            'appid.required' => '缺少参数appid',
            'redirect_uri.required' => '缺少参数redirect_uri',
            'scope.required' => '缺少参数scope',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
                'message' => $message[0]
            ]);
        }

        return view('vendor.passport.prepare_authorize');
    }

    public function authorize(Request $request)
    {
        $request_data = $request->all();

        $validator = validator($request_data, [
            'appid' => 'required',
            'secret' => 'required',
            'code' => 'required',
            'grant_type' => 'required',
        ], [
            'appid.required' => '缺少参数appid',
            'secret.required' => '缺少参数secret',
            'code.required' => '缺少参数code',
            'grant_type.required' => '缺少参数grant_type',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
                'message' => $message[0]
            ]);
        }

        $appid = openssl_decrypt(base64_decode($request_data['appid']), 'AES-256-ECB', config('aes-key'));

        if (!$appid) {
            return response()->json([
                'message' => '不支持普惠通平台的Appid'
            ]);
        }

        $client = OauthClient::query()
            ->select(['id', 'name', 'redirect'])
            ->where([
                'id' => $appid,
                'secret' => $request_data['secret']
            ])
            ->first();

        if (!$client) {
            return response()->json([
                'message' => '不支持普惠通平台的Appid'
            ]);
        }


        try {
            $http = new Client();

            $response = $http->post(url('/oauth/token'), [
                'form_params' => [
                    'grant_type' => $request_data['grant_type'],
                    'client_id' => $appid,
                    'client_secret' => $request_data['secret'],
                    'code' => $request_data['code'],
                    'redirect_uri' => $client->redirect,
                ]
            ]);

            $result = json_decode((string)$response->getBody(), true);

            $params = [
                'appid' => $request_data['appid'],
                'secret' => $request_data['secret'],
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
                    'message' => '验证失败，请重新获取验证' . $response_data['errorCode']
                ]);
            }

            return [
                'secretKey' => $result['access_token'],
                'expires_in' => $result['expires_in'],
                'refresh_secretKey' => $result['refresh_token'],
                'openid' => $response_data['openid']
            ];
        } catch (\Exception $e) {
            return response()->json([
                'message' => '验证失败，请检查code是否有效'
            ]);
        }
    }

    public function prepareAuthorize(Request $request)
    {
        $request_data = $request->all();

        $validator = validator($request_data, [
            'response_type' => 'required',
            'appid' => 'required',
            'redirect_uri' => 'required',
            'scope' => 'required',
        ], [
            'response_type.required' => '缺少参数response_type',
            'appid.required' => '缺少参数appid',
            'redirect_uri.required' => '缺少参数redirect_uri',
            'scope.required' => '缺少参数scope',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
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

        $appid = openssl_decrypt(base64_decode($request_data['appid']), 'AES-256-ECB', config('aes-key'));

        if (!$appid) {
            return response()->json([
                'message' => '不支持普惠通平台的Appid'
            ]);
        }

        $client = OauthClient::query()
            ->select(['id', 'name', 'redirect'])
            ->where('id', $appid)
            ->first();

        if (!$client) {
            return response()->json([
                'message' => '不支持普惠通平台的Appid'
            ]);
        }

        if ($request_data['redirect_uri'] !== $client->redirect) {
            return response()->json([
                'message' => 'redirect_uri域名与后台配置不一致'
            ]);
        }

        $query = http_build_query([
            'client_id' => $appid,
            'redirect_uri' => $request['redirect_uri'],
            'response_type' => 'code',
            'scope' => $request_data['scope'],
        ]);

        return redirect(url('oauth/authorize') . '?' . $query);
    }
}