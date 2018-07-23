<?php
/**
 * Created by PhpStorm.
 * User: vin
 * Date: 2018/7/4
 * Time: 上午8:57
 */

namespace App\Http\Controllers\User;

use App\Eloquent\MemberMeta;
use App\Eloquent\OauthClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $request_data = $request->all();

        $validator = validator($request_data, [
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => '缺少参数username',
            'password.required' => '缺少参数password',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
                'message' => $message[0]
            ]);
        }
        // jwt
        if (!Auth::guard('web')->attempt([
            'name' => $request_data['username'],
            'password' => $request_data['password']
        ])) {
            return response()->json([
                'message' => '认证失败'
            ]);
        };

        //return 'ok';
        return redirect()->back();
    }

    public function getUserInfo(Request $request)
    {
        $user = Auth::guard('api')->user();

        $openid = $request->input('open_id', '');

        if (empty($openid)) {
            return response()->json([
                'errorCode' => '100000',
                'message' => '缺少参数open_id'
            ]);
        }

        if (!$user) {
            return response()->json([
                'errorCode' => '200000',
                'message' => '该用户不存在'
            ]);
        }

        $channel = MemberMeta::query()->where([
            'meta_value' => $openid,
            'member_id' => $user->id,
        ])->where('meta_key', 'like', '%\_openid')
        ->value('meta_key');

        if (!$channel) {
            return response()->json([
                'errorCode' => '400006',
                'message' => '该openid不存在'
            ]);
        }

        $user_info = [
            'errorCode' => '000000',
            'open_id' => $openid,
            'nickname' => $user->nickname,
            'sex' => $user->sex,
            'mobile' => $user->phone,
            'headimgurl' => $user->avatar ?: '',
        ];

        return response()->json($user_info);
    }

    public function username()
    {
        return 'name';
    }

    public function getUserOpenid(Request $request)
    {
        Log::info('==========oauth获取用户openid start==========');

        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'errorCode' => '200000',
                'message' => '该用户不存在',
            ]);
        }

        $user_id = Auth::guard('api')->user()->id;

        $request_data = $request->all();

        Log::info('oauth获取用户openid接收参数：' . json_encode($request_data));

        $client_id = $request->input('client_id', '');
        $secret = $request->input('secret', '');

        if (empty($client_id) || empty($secret)) {
            return [
                'errorCode' => '100000',
                'openid' => '',
                'message' => '缺少参数'
            ];
        }

        $client_id_ = openssl_decrypt(base64_decode($client_id), 'AES-256-ECB', config('aes-key'));

        $client_key = OauthClient::query()
            ->where('id', $client_id_)->value('key');

        if (empty($client_key)) {
            return [
                'errorCode' => '400004',
                'openid' => '',
                'message' => '渠道验证失败'
            ];
        }

        try {
            $openid = openssl_encrypt($user_id . '_' . $client_id, 'AES-128-ECB', $secret);

            $user_openid = \DB::select('select meta_value from member_meta where member_id = ? and meta_key = ?'
                , [$user_id, $client_key . '_openid']);

            if (!empty($user_openid)) {

                $user_openid = $user_openid[0]->meta_value;

                if ($user_openid !== $openid) {
                    return [
                        'errorCode' => '400005',
                        'openid' => '',
                        'message' => '该渠道下的openid不正确'
                    ];
                }
            } else {
                \DB::insert('insert into member_meta (member_id, meta_key, meta_value, created_at) values(?,?,?,?)'
                    , [$user_id, $client_key . '_openid', $openid, date('Y-m-d H:i:s')]);
            }
        } catch (\Exception $e) {
            Log::error('获取用户openid异常：' . $e->getLine() . '-->' . $e->getMessage());
            return [
                'errorCode' => '440001',
                'openid' => '',
                'message' => '验证系统异常'
            ];
        }

        return [
            'errorCode' => '000000',
            'openid' => $openid,
            'message' => 'ok',
        ];
    }
}