<?php

namespace App\Providers;

use App\Contracts\DeliveryPricing;
use App\Contracts\PushNotifier;
use App\Contracts\SmsSender;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Pricing\FlatDeliveryPricing;
use App\Services\Push\LogPushNotifier;
use App\Services\Settings\SettingsRepository;
use App\Services\Sms\LogSmsSender;
use App\View\Composers\AdminLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);

        // Kengaytirish nuqtalari: drayverni almashtirish uchun faqat shu yerni o'zgartiring
        $this->app->bind(DeliveryPricing::class, FlatDeliveryPricing::class);

        $this->app->bind(SmsSender::class, fn () => match (config('delivery.sms.driver')) {
            'log' => new LogSmsSender,
            default => throw new InvalidArgumentException('Noma\'lum SMS drayveri: '.config('delivery.sms.driver')),
        });

        $this->app->bind(PushNotifier::class, fn () => match (config('delivery.push.driver')) {
            'log' => new LogPushNotifier,
            default => throw new InvalidArgumentException('Noma\'lum push drayveri: '.config('delivery.push.driver')),
        });
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Paginator::defaultView('admin.partials.pagination');
        View::composer('layouts.admin', AdminLayoutComposer::class);

        // Admin URL'larida foydalanuvchi faqat kerakli rolda topiladi (aks holda 404)
        Route::bind('courier', fn (string $id) => User::query()->role(UserRole::Courier)->findOrFail($id));
        Route::bind('client', fn (string $id) => User::query()->role(UserRole::Client)->findOrFail($id));
        Route::bind('staff', fn (string $id) => User::query()
            ->whereIn('role', array_map(fn (UserRole $r) => $r->value, UserRole::staff()))->findOrFail($id));

        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute(3)->by('otp:'.$request->input('phone')),
            Limit::perMinute(10)->by('otp-ip:'.$request->ip()),
        ]);
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by('login:'.$request->ip()));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
    }
}
