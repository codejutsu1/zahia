<?php

use App\Mail\OrderShipped;
use App\Models\Order;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mail', function () {
    $order = Order::find(1);

    return new OrderShipped($order);
});

require __DIR__.'/webhooks.php';
