<?php
/**
 * Created by PhpStorm.
 * User: vin
 * Date: 2018/7/3
 * Time: 上午10:31
 */

namespace App\Eloquent;


use Illuminate\Database\Eloquent\Model;

class OauthClient extends Model
{
    protected $table = 'oauth_clients';

    protected $hidden = [
        'secret',
    ];
}