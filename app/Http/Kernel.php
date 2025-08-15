<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;


class Kernel extends HttpKernel
{
    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'admin' => \App\Http\Middleware\AdminMiddleware::class, // ✅ must be exactly like this
];



}
