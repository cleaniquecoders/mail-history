<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('mailhistory.ui.prefix', 'mailhistory'),
    'middleware' => config('mailhistory.ui.middleware', ['web', 'auth']),
    'as' => config('mailhistory.ui.name', 'mailhistory.'),
], function () {
    Route::get('/', function () {
        return view('mailhistory::index');
    })->name('dashboard');
});
