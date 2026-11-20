<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Moderator va administratorlar (faqat admin boshqaradi). */
class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff.index', [
            'title' => 'Xodimlar',
            'staff' => User::query()
                ->whereIn('role', array_map(fn (UserRole $r) => $r->value, UserRole::staff()))
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.form', ['title' => 'Yangi xodim', 'staff' => null, 'roles' => UserRole::staff()]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        User::query()->create($request->validated());

        return redirect()->route('admin.staff.index')->with('success', 'Xodim qo\'shildi.');
    }

    public function edit(User $staff): View
    {
        return view('admin.staff.form', ['title' => 'Xodimni tahrirlash', 'staff' => $staff, 'roles' => UserRole::staff()]);
    }

    public function update(StaffRequest $request, User $staff): RedirectResponse
    {
        $data = $request->validated();
        if ($staff->is(auth()->user()) && ($data['role'] !== UserRole::Admin->value || ! $data['is_active'])) {
            throw new BusinessRuleException('O\'zingizni bloklay yoki admin huquqidan mahrum qila olmaysiz.');
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $staff->update($data);

        return redirect()->route('admin.staff.index')->with('success', 'Saqlandi.');
    }
}
