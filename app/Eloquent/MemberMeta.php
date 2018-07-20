<?php
/**
 * Created by PhpStorm.
 * User: vin
 * Date: 2018/7/20
 * Time: 上午9:17
 */

namespace App\Eloquent;


use Illuminate\Database\Eloquent\Model;

class MemberMeta extends Model
{
    protected $table = 'member_meta';

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
}