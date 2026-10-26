<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use View;
use App;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // queue:work must not crash where pcntl is loaded but its functions are disabled (shared hosting)
        $this->app->extend('queue.worker', function ($worker) {
            return \App\Queue\SignalSafeWorker::fromWorker($worker);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrap();

        \Illuminate\Support\Facades\Response::macro('shopifyFrame', function ($response, $shop) {
            return $response->header('Content-Security-Policy', 'frame-ancestors https://' . $shop . ' https://admin.shopify.com');
        });

        App::singleton('authUser', function () {
            return Auth::user();
        });

        View::composer('*', function ($view) {
            $view->with('authUser', Auth::user());
        });
    }
}
