<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = User::query()
            ->where('phone', $request->validated('phone'))
            ->whereIn('role', array_map(fn (UserRole $r) => $r->value, UserRole::staff()))
            ->first();

        if (! $user || ! $user->password || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['phone' => 'Telefon raqam yoki parol noto\'g\'ri.']);
        }
        if (! $user->is_active) {
            throw ValidationException::withMessages(['phone' => 'Akkauntingiz bloklangan.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
