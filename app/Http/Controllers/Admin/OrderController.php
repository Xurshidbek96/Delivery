<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\AssignCourier;
use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\ConfirmOrder;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignCourierRequest;
use App\Http\Requests\Admin\CancelOrderRequest;
use App\Http\Requests\Admin\ConfirmOrderRequest;
use App\Http\Requests\Admin\StaffNoteRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\Couriers\CourierLocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Tablar: kalit → shu tabdagi holatlar */
    private const TABS = [
        'all' => [],
        'pending' => [OrderStatus::Pending],
        'confirmed' => [OrderStatus::Confirmed],
        'active' => [OrderStatus::Assigned, OrderStatus::Shopping, OrderStatus::Delivering],
        'delivered' => [OrderStatus::Delivered],
        'cancelled' => [OrderStatus::Cancelled],
    ];

    private const TAB_LABELS = [
        'all' => 'Barchasi',
        'pending' => 'Yangi',
        'confirmed' => 'Kuryer kutmoqda',
        'active' => 'Jarayonda',
        'delivered' => 'Yetkazilgan',
        'cancelled' => 'Bekor qilingan',
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists($request->query('status'), self::TABS) ? $request->query('status') : 'all';

        $orders = Order::query()
            ->with(['client', 'courier'])
            ->withCount('items')
            ->when(self::TABS[$tab], fn ($q, $statuses) => $q->status(...$statuses))
            ->search($request->query('q'))
            ->when($request->query('courier_id'), fn ($q, $id) => $q->where('courier_id', $id))
            ->when($request->date('from'), fn ($q, $d) => $q->where('created_at', '>=', $d->startOfDay()))
            ->when($request->date('to'), fn ($q, $d) => $q->where('created_at', '<=', $d->endOfDay()))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $byStatus = Order::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $counts = collect(self::TABS)->map(fn (array $statuses) => $statuses === []
            ? (int) $byStatus->sum()
            : (int) collect($statuses)->sum(fn (OrderStatus $s) => $byStatus[$s->value] ?? 0));

        return view('admin.orders.index', [
            'title' => 'Buyurtmalar',
            'orders' => $orders,
            'tab' => $tab,
            'tabs' => self::TAB_LABELS,
            'counts' => $counts,
            'couriers' => User::query()->role(UserRole::Courier)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Order $order, CourierLocator $locator): View
    {
        $order->load(['items', 'client', 'courier.courierProfile', 'histories.actor', 'confirmer']);

        $canAssign = in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Assigned], true);

        return view('admin.orders.show', [
            'title' => "Buyurtma {$order->number}",
            'order' => $order,
            'candidates' => $canAssign ? $locator->candidatesFor($order) : collect(),
            'canAssign' => $canAssign,
            'clientOrdersCount' => $order->client->clientOrders()->count(),
        ]);
    }

    public function confirm(ConfirmOrderRequest $request, Order $order, ConfirmOrder $action): RedirectResponse
    {
        $action->handle(
            $order,
            $request->user(),
            $request->filled('delivery_fee') ? $request->integer('delivery_fee') : null,
            $request->validated('staff_note'),
        );

        return back()->with('success', "{$order->number} tasdiqlandi. Endi kuryerni tanlang.");
    }

    public function assign(AssignCourierRequest $request, Order $order, AssignCourier $action): RedirectResponse
    {
        $courier = User::query()->findOrFail($request->integer('courier_id'));
        $action->handle($order, $courier, $request->user());

        return back()->with('success', "{$order->number} → {$courier->name}. Kuryerga xabar yuborildi.");
    }

    public function cancel(CancelOrderRequest $request, Order $order, CancelOrder $action): RedirectResponse
    {
        $action->handle($order, $request->user(), $request->validated('reason'));

        return back()->with('success', "{$order->number} bekor qilindi.");
    }

    public function note(StaffNoteRequest $request, Order $order): RedirectResponse
    {
        $order->update(['staff_note' => $request->validated('staff_note')]);

        return back()->with('success', 'Izoh saqlandi.');
    }

    /** Yon paneldagi belgi va ovozli ogohlantirish uchun (har 15 soniyada) */
    public function pendingCount(): JsonResponse
    {
        return response()->json(['pending' => Order::query()->status(OrderStatus::Pending)->count()]);
    }
}
