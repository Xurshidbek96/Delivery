<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = User::query()
            ->role(UserRole::Client)
            ->withCount('clientOrders')
            ->withSum(['clientOrders as spent' => fn ($q) => $q->where('status', OrderStatus::Delivered->value)], 'total')
            ->withMax('clientOrders as last_order_at', 'created_at')
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', '%'.preg_replace('/\D+/', '', $term).'%')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.index', ['title' => 'Mijozlar', 'clients' => $clients]);
    }

    public function show(User $client): View
    {
        return view('admin.clients.show', [
            'title' => $client->name,
            'client' => $client->load('addresses'),
            'orders' => $client->clientOrders()->with('courier')->withCount('items')->latest('id')->paginate(15),
            'spent' => (int) $client->clientOrders()->status(OrderStatus::Delivered)->sum('total'),
        ]);
    }

    public function toggle(User $client): RedirectResponse
    {
        $client->update(['is_active' => ! $client->is_active]);
        if (! $client->is_active) {
            $client->tokens()->delete();
        }

        return back()->with('success', $client->is_active ? 'Mijoz blokdan chiqarildi.' : 'Mijoz bloklandi.');
    }
}
