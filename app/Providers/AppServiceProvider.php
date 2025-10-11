<?php

namespace App\Providers;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       Schema::defaultStringLength(191);
           Blade::if('canaccess', function ($permission) {
        $user = auth('admin')->user();
        $permissions = json_decode($user->permissions ?? '[]', true);
        return in_array($permission, $permissions);
    });
    }
}
