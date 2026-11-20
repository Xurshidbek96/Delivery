<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourierVehicle;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourierRequest;
use App\Models\User;
use App\Services\Users\CourierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CourierController extends Controller
{
    public function index(Request $request): View
    {
        $active = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::courierActive());

        $couriers = User::query()
            ->role(UserRole::Courier)
            ->with('courierProfile')
            ->withCount([
                'courierOrders as active_orders_count' => fn ($q) => $q->whereIn('status', $active),
                'courierOrders as delivered_count' => fn ($q) => $q->where('status', OrderStatus::Delivered->value),
            ])
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', '%'.preg_replace('/\D+/', '', $term).'%')))
            ->when($request->query('state') === 'online', fn ($q) => $q->whereHas('courierProfile', fn ($p) => $p->where('is_online', true)))
            ->when($request->query('state') === 'blocked', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.couriers.index', ['title' => 'Kuryerlar', 'couriers' => $couriers]);
    }

    public function create(): View
    {
        return view('admin.couriers.form', ['title' => 'Yangi kuryer', 'courier' => null, 'vehicles' => CourierVehicle::cases()]);
    }

    public function store(CourierRequest $request, CourierService $service): RedirectResponse
    {
        $courier = $service->save($request->validated());

        return redirect()->route('admin.couriers.show', $courier)->with('success', 'Kuryer qo\'shildi. Login: telefon raqam va siz kiritgan parol.');
    }

    public function show(User $courier): View
    {
        $courier->load('courierProfile');

        $delivered = $courier->courierOrders()->status(OrderStatus::Delivered);

        return view('admin.couriers.show', [
            'title' => $courier->name,
            'courier' => $courier,
            'stats' => [
                'active' => $courier->activeCourierOrdersCount(),
                'delivered' => (clone $delivered)->count(),
                'delivered_today' => (clone $delivered)->where('delivered_at', '>=', Carbon::today())->count(),
                'turnover' => (int) (clone $delivered)->sum('total'),
            ],
            'orders' => $courier->courierOrders()->with('client')->withCount('items')->latest('id')->paginate(15),
        ]);
    }

    public function edit(User $courier): View
    {
        return view('admin.couriers.form', ['title' => 'Kuryerni tahrirlash', 'courier' => $courier->load('courierProfile'), 'vehicles' => CourierVehicle::cases()]);
    }

    public function update(CourierRequest $request, User $courier, CourierService $service): RedirectResponse
    {
        $service->save($request->validated(), $courier);

        return redirect()->route('admin.couriers.show', $courier)->with('success', 'Saqlandi.');
    }
}
