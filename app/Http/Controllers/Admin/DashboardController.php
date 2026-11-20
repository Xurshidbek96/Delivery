<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today();
        $deliveredToday = Order::query()->status(OrderStatus::Delivered)->where('delivered_at', '>=', $today);

        $stats = [
            'today' => Order::query()->where('created_at', '>=', $today)->count(),
            'pending' => Order::query()->status(OrderStatus::Pending)->count(),
            'in_progress' => Order::query()->status(OrderStatus::Confirmed, ...OrderStatus::courierActive())->count(),
            'delivered_today' => (clone $deliveredToday)->count(),
            'turnover_today' => (int) (clone $deliveredToday)->sum('total'),
            'income_today' => (int) (clone $deliveredToday)->selectRaw('COALESCE(SUM(delivery_fee + service_fee), 0) as income')->value('income'),
            'couriers_online' => CourierProfile::query()->where('is_online', true)
                ->whereHas('user', fn ($q) => $q->active())->count(),
            'couriers_total' => User::query()->role(UserRole::Courier)->active()->count(),
        ];

        return view('admin.dashboard', [
            'title' => 'Bosh sahifa',
            'stats' => $stats,
            'week' => $this->lastSevenDays(),
            'openOrders' => Order::query()
                ->status(...OrderStatus::open())
                ->with(['client', 'courier'])
                ->withCount('items')
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'confirmed' THEN 1 ELSE 2 END")
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }

    /** @return list<array{label: string, count: int}> */
    private function lastSevenDays(): array
    {
        $from = Carbon::today()->subDays(6);
        $counts = Order::query()
            ->where('created_at', '>=', $from)
            ->pluck('created_at')
            ->countBy(fn (Carbon $d) => $d->toDateString());

        $days = ['Ya', 'Du', 'Se', 'Ch', 'Pa', 'Ju', 'Sh'];

        return collect(range(0, 6))->map(function (int $i) use ($from, $counts, $days) {
            $day = $from->copy()->addDays($i);

            return ['label' => $days[$day->dayOfWeek].' '.$day->format('d'), 'count' => (int) ($counts[$day->toDateString()] ?? 0)];
        })->all();
    }
}
