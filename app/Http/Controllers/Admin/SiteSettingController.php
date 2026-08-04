<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', ['setting' => SiteSetting::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'maintenance_mode' => ['boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $setting = SiteSetting::current();
        $setting->update([
            'maintenance_mode' => $request->boolean('maintenance_mode'),
            'maintenance_message' => $validated['maintenance_message'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        Cache::forget('site_setting');

        AuditLog::record('maintenance_mode_toggled', 'SiteSetting', $setting->id);

        return back()->with('admin_status', 'Settings saved.');
    }
}
