<?php

use Illuminate\Support\Facades\Route;

Route::webhooks('webhooks/incoming-message', 'twilio');
// Route::webhooks('webhooks/twilio');

Route::webhooks('webhooks/flutterwave', 'flutterwave');
