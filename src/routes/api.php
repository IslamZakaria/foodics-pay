<?php

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/{bankType}', [WebhookController::class, 'handle']);

Route::post('/payments/transfer', [PaymentController::class, 'transfer']);