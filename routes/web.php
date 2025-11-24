<?php

use App\Mail\OrderDelivered;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mail', function () {
    return new OrderDelivered;
});

require __DIR__.'/webhooks.php';
