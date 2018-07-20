<?php
/**
 * Created by PhpStorm.
 * User: vin
 * Date: 2018/7/9
 * Time: 上午9:52
 */

return [
    'aes-key' => 'O4ZutDL9UEABzc6bEEyrPYQCgadU1dLS',

    'debug' => true,

    'debug_conf' => [
        'callback_unionpay_api_server' => 'https://staging.phjrt.com/api/notify/callback/unionpay',
//        'callback_unionpay_api_server' => 'http://jhpm.beta/api/notify/callback/unionpay',

    ],

    'production_conf' => [
        'callback_unionpay_api_server' => 'https://api.phjrt.com/api/notify/callback/unionpay',
    ],
];