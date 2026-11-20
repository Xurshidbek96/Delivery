<?php

use App\Http\Controllers\Api\V1\Auth\CourierAuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Client\AddressController;
use App\Http\Controllers\Api\V1\Client\OrderController as ClientOrderController;
use App\Http\Controllers\Api\V1\Courier\OrderController as CourierOrderController;
use App\Http\Controllers\Api\V1\Courier\ProfileController as CourierProfileController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Support\Facades\Route;

/*
| REST API v1 — mijoz va kuryer mobil ilovalari uchun. Hujjat: docs/api.md
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('meta', MetaController::class)->name('meta');

    // --- Avtorizatsiya ---
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('otp/send', [OtpController::class, 'send'])->middleware('throttle:otp')->name('otp.send');
        Route::post('otp/verify', [OtpController::class, 'verify'])->middleware('throttle:login')->name('otp.verify');
        Route::post('courier/login', [CourierAuthController::class, 'login'])->middleware('throttle:login')->name('courier.login');
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // --- Umumiy (mijoz va kuryer) ---
        Route::middleware('role:client,courier')->group(function () {
            Route::get('me', [SessionController::class, 'me'])->name('me');
            Route::patch('me', [SessionController::class, 'update'])->name('me.update');
            Route::post('auth/logout', [SessionController::class, 'logout'])->name('auth.logout');

            Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
            Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

            Route::post('devices', [DeviceTokenController::class, 'store'])->name('devices.store');
            Route::delete('devices', [DeviceTokenController::class, 'destroy'])->name('devices.destroy');
        });

        // --- Mijoz ilovasi ---
        Route::middleware('role:client')->prefix('client')->name('client.')->group(function () {
            Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
            Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
            Route::put('addresses/{address}', [AddressController::class, 'update'])->whereNumber('address')->name('addresses.update');
            Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->whereNumber('address')->name('addresses.destroy');

            Route::get('orders', [ClientOrderController::class, 'index'])->name('orders.index');
            Route::post('orders', [ClientOrderController::class, 'store'])->name('orders.store');
            Route::get('orders/{order}', [ClientOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
            Route::post('orders/{order}/cancel', [ClientOrderController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');
        });

        // --- Kuryer ilovasi ---
        Route::middleware('role:courier')->prefix('courier')->name('courier.')->group(function () {
            Route::patch('online', [CourierProfileController::class, 'online'])->name('online');
            Route::post('location', [CourierProfileController::class, 'location'])->name('location');

            Route::get('orders', [CourierOrderController::class, 'index'])->name('orders.index');
            Route::prefix('orders/{order}')->whereNumber('order')->name('orders.')->group(function () {
                Route::get('/', [CourierOrderController::class, 'show'])->name('show');
                Route::post('accept', [CourierOrderController::class, 'accept'])->name('accept');
                Route::post('decline', [CourierOrderController::class, 'decline'])->name('decline');
                Route::patch('items/{item}', [CourierOrderController::class, 'updateItem'])->whereNumber('item')->name('items.update');
                Route::post('purchase-complete', [CourierOrderController::class, 'completePurchase'])->name('purchase-complete');
                Route::post('deliver', [CourierOrderController::class, 'deliver'])->name('deliver');
            });
        });
    });
});
