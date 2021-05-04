<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Hivokas\LaravelPassportSocialGrant\Resolvers\SocialUserResolverInterface;
use App\Services\SocialUserResolver;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
    }
    
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [
        SocialUserResolverInterface::class => SocialUserResolver::class,
    ];
}
