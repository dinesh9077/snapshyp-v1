<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Setting;  
class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
		config(
        [
            'setting' => Setting::all([
            'name','value'
            ])->keyBy('name')->transform(function ($setting) 
            {
                return $setting->value; 
            })->toArray()
        ]);  
    }
}
