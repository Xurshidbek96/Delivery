<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'create'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
    });

    // Moderator va admin
    Route::middleware(['auth', 'role:moderator,admin'])->group(function () {
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('orders/pending-count', [OrderController::class, 'pendingCount'])->name('orders.pending-count');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
        Route::post('orders/{order}/assign', [OrderController::class, 'assign'])->name('orders.assign');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::patch('orders/{order}/note', [OrderController::class, 'note'])->name('orders.note');

        Route::resource('couriers', CourierController::class)->except('destroy');

        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::post('clients/{client}/toggle', [ClientController::class, 'toggle'])->name('clients.toggle');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');

        // Faqat admin
        Route::middleware('role:admin')->group(function () {
            Route::resource('staff', StaffController::class)->except(['show', 'destroy'])->parameters(['staff' => 'staff']);
            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        });
    });
});
