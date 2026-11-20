<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Services\Settings\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(SettingsRepository $settings): View
    {
        return view('admin.settings.edit', ['title' => 'Sozlamalar', 'settings' => $settings->all()]);
    }

    public function update(SettingsRequest $request, SettingsRepository $settings): RedirectResponse
    {
        $settings->set($request->validated());

        return back()->with('success', 'Sozlamalar saqlandi.');
    }
}
