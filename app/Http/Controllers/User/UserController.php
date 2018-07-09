<?php
/**
 * Created by PhpStorm.
 * User: vin
 * Date: 2018/7/4
 * Time: 上午8:57
 */

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if(Auth::guard('web')->attempt([
            'name' => $request_data['username'],
            'password' => $request_data['password']
        ])) {
            return response()->json([
                'message' => '认证失败'
            ]);
        };

        return redirect('/');
//        $user = Auth::guard('web')->user();
//
//        return [
//            'access_token' => $user->createToken('admin')->accessToken
//        ];
    }

    public function username()
    {
        return 'name';
    }
}