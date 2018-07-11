<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
        Passport::routes();
        Passport::tokensExpireIn(now()->addHours(2));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::tokensCan([
            'snsapi_userinfo' => '获取普惠通用户信息',
        ]);
    }
}
