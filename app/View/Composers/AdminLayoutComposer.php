<?php

namespace App\View\Composers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\View\View;

/** Har bir admin sahifasiga: tasdiqlash kutayotgan buyurtmalar soni (yon panel belgisi). */
class AdminLayoutComposer
{
    public function compose(View $view): void
    {
        $view->with('pendingCount', Order::query()->status(OrderStatus::Pending)->count());
    }
}
