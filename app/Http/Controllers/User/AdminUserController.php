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
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthManager;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Zend\Diactoros\Response as Psr7Response;
use League\OAuth2\Server\AuthorizationServer;

class AdminUserController extends Controller
{
//    use AuthenticatesUsers;
//
//    protected $auth;
//    protected $server;
//
//    public function __construct(AuthManager $authManager, AuthorizationServer $server)
//    {
//        $this->auth = $authManager;
//        $this->server = $server;
//    }



    public function token(Request $request)
    {
        $request_data = $request->all();

        $validator = validator($request_data, [
//            'grant_type' => 'required',
//            'appid' => 'required',
//            'secret' => 'required',
            'username' => 'required',
            'password' => 'required',
        ], [
//            'grant_type.required' => '缺少参数grant_type',
//            'appid.required' => '缺少参数appid',
//            'secret.required' => '缺少参数secret',
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

//        try {
//            $request_tmp = $request;
//
//            $request_tmp->offsetSet('grant_type', 'password');
//            $request_tmp->offsetSet('client_id', $request_data['appid']);
//            $request_tmp->offsetSet('client_secret', $request_data['secret']);
//            $request_tmp->offsetSet('username', $request_data['username']);
//            $request_tmp->offsetSet('password', $request_data['password']);
//            $request_tmp->offsetSet('scope', '*');
//
//
//            $request_t = (new DiactorosFactory)->createRequest($request_tmp);
//
//            $back = $this->server->respondToAccessTokenRequest($request_t, new Psr7Response());
//            $back = json_decode((string)$back->getBody(), true);
//
//            dd($back);
//            if (isset($back['access_token']) && isset($back['refresh_token'])) {
//                return $this -> withCode(200)
//                    -> withData($back)
//                    -> withMessage('vuser::login.1000');
//            }
//
//
////            $http = new Client();
////            $response = $http->post(url('/oauth/token'), [
////                'form_params' => [
////                    'grant_type' => 'password',
////                    'client_id' => $request_data['appid'],
////                    'client_secret' => $request_data['secret'],
////                    'username' => $request_data['username'],
////                    'password' => $request_data['password'],
////                    'scope' => '*',
////                ],
////            ]);
////            return json_decode((string) $response->getBody(), true);
//
//        } catch (\Exception $e) {
//            dd($e->getMessage(),$e->getTrace());
//            return response()->json([
//                'message' => '获取token失败'
//            ]);
//        }
    }

    public function username()
    {
        return 'name';
    }
}