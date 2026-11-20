<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use Illuminate\Contracts\Container\Container;

/** To'lov usuli bo'yicha drayverni tanlaydi. Yangi drayver — $gateways ga qo'shing. */
class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    private array $gateways = [
        'cash' => CashPaymentGateway::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function for(PaymentMethod $method): PaymentGateway
    {
        $class = $this->gateways[$method->value] ?? null;

        if ($class === null || ! $method->isEnabled()) {
            throw new BusinessRuleException("{$method->label()} to'lov usuli hozircha ishlamaydi.");
        }

        return $this->container->make($class);
    }
}
