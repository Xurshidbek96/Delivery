<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_happy_path_transitions_are_allowed(): void
    {
        $path = [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Assigned, OrderStatus::Shopping, OrderStatus::Delivering, OrderStatus::Delivered];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertTrue($path[$i]->canTransitionTo($path[$i + 1]), "{$path[$i]->value} → {$path[$i + 1]->value}");
        }
    }

    public function test_steps_cannot_be_skipped_and_final_states_are_closed(): void
    {
        $this->assertFalse(OrderStatus::Pending->canTransitionTo(OrderStatus::Assigned));
        $this->assertFalse(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Shopping));
        $this->assertFalse(OrderStatus::Shopping->canTransitionTo(OrderStatus::Delivered));
        $this->assertSame([], OrderStatus::Delivered->allowedTransitions());
        $this->assertSame([], OrderStatus::Cancelled->allowedTransitions());
    }

    public function test_client_can_cancel_only_before_shopping(): void
    {
        $this->assertTrue(OrderStatus::Assigned->isCancellableByClient());
        $this->assertFalse(OrderStatus::Shopping->isCancellableByClient());
        $this->assertFalse(OrderStatus::Delivering->isCancellableByClient());
    }

    public function test_phone_normalization(): void
    {
        $this->assertSame('+998901234567', Phone::normalize('90 123 45 67'));
        $this->assertSame('+998901234567', Phone::normalize('+998 (90) 123-45-67'));
        $this->assertNull(Phone::normalize('12345'));
        $this->assertSame('+998 90 123 45 67', Phone::format('+998901234567'));
    }
}
