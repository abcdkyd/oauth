<?php
/**
 * Created by PhpStorm.
 * User: vin
 * Date: 2018/7/9
 * Time: 上午9:16
 */

namespace App\Http\Controllers\Clients;


use App\Eloquent\OauthClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;

class ClientsController extends BaseController
{
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
//        $query = http_build_query([
//            'client_id' => $appid,
//            'redirect_uri' => $request['redirect_uri'],
//            'response_type' => 'code',
//            'scope' => '',
//        ]);
//
//        return redirect(url('oauth/authorize') .'?'.$query);
    }

    public function authorize(Request $request) {

        $request_data = $request->all();

        $http = new Client();

        $response = $http->post(url('/oauth/authorize'), [
            'form_params' => [
                'response_type' => 'code',
                'client_id' => $request_data['client_id'],
                'redirect_uri' => $request_data['redirect_uri'],
                'scope' => '',
            ]
        ])->withHeader('Authorization', 'Bearer ' . $request_data['access_token']);

        return json_decode((string) $response->getBody(), true);
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
            $stat_id = openssl_decrypt(base64_decode($request_data['stat']), 'AES-256-ECB', config('aes-key'));
            Auth::guard()->loginUsingId($stat_id);
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
            'scope' => '',
        ]);

        return redirect(url('oauth/authorize') .'?'.$query);
    }
}