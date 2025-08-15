<?php

use Illuminate\Support\Facades\Route;

Route::get('/', action: function () {
    return view('index');
});

Route::get('/parking-slots', function () {
    return view('parking-slots');
})->name('parking.slots');

Route::get('/users', function () {
    return view('users');
})->name('users');


Route::get('/sample', function () {
    return view('sample');
});

Route::get('/scan-status', function () {
    return view('scan-status');
});
