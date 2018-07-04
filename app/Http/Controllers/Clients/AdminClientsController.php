<?php
/**
 * Created by PhpStorm.
 * User: vin
 * Date: 2018/7/3
 * Time: 上午10:45
 */

namespace App\Http\Controllers\Clients;


use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class AdminClientsController extends Controller
{
    public function createClient(Request $request)
    {

    }

    public function token(Request $request)
    {
        $request_data = $request->all();

        $validator = validator($request_data, [
            'grant_type' => 'required',
            'appid' => 'required',
            'secret' => 'required',
        ], [
            'grant_type.required' => '缺少参数grant_type',
            'appid.required' => '缺少参数appid',
            'secret.required' => '缺少参数secret',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
                'message' => $message[0]
            ]);
        }

        if ($request_data['grant_type'] !== 'client_credential') {
            return response()->json([
                'message' => '请确保grant_type字段值为client_credential'
            ]);
        }

        try {
            $http = new Client();
            $response = $http->post(url('/oauth/token'), [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $request_data['appid'],
                    'client_secret' => $request_data['secret'],
                    'scope' => '',
                ],
            ]);
            return json_decode((string) $response->getBody(), true);

        } catch (\Exception $e) {
            return response()->json([
                'message' => '获取token失败'
            ]);
        }
    }
}