<?php
/**
 * Created by PhpStorm.
 * User: vin
 * Date: 2018/7/4
 * Time: 上午8:57
 */

namespace App\Http\Controllers\User;


use App\Eloquent\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{

    public function getUserInfo(Request $request)
    {
        $request_data = $request->all();

        $validator = validator($request_data, [
            'access_token' => 'required',
            'openid' => 'required',
        ], [
            'access_token.required' => '缺少参数access_token',
            'openid.required' => '缺少参数openid',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->getMessages();
            $message = reset($message);
            return response()->json([
                'message' => $message[0]
            ]);
        }

        $user = User::query()
            ->select([
                'phone',
                'avatar',
                'birthday',
                'nickname',
                'realname',
                'sex',
            ])
            ->where('id', $request_data['openid'])
            ->where('status', 'normal')
            ->first();

        if ($user) {
            $user = $user->toArray();
            return $user;
        }

        return response()->json([
            'message' => '该用户不存在'
        ]);
    }

    public function token(Request $request)
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
        if(!Auth::guard('web')->attempt([
            'name' => $request_data['username'],
            'password' => $request_data['password']
        ])) {
            return response()->json([
                'message' => '认证失败'
            ]);
        };

        $user = Auth::guard('web')->user();

        return [
            'access_token' => $user->createToken('admin')->accessToken
        ];
    }

    public function username()
    {
        return 'name';
    }
}