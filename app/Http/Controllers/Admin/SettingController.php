<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ModerationAudit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['settings' => Setting::pluck('value', 'key')->all()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['site_name' => 'required|string|max:100', 'site_tagline' => 'required|string|max:200', 'site_description' => 'required|string|max:320', 'about' => 'nullable|string|max:10000', 'contact_email' => 'nullable|email|max:255', 'community_rules' => 'nullable|string|max:10000']);
        $labels = $request->validate(['report_reason_labels' => 'sometimes|array:spam,harassment,hate,other', 'report_reason_labels.*' => 'required|string|max:150']);
        if (isset($labels['report_reason_labels'])) {
            $data['report_reason_labels'] = json_encode($labels['report_reason_labels'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        DB::transaction(function () use ($request, $data): void {
            $before = Setting::whereIn('key', array_keys($data))->lockForUpdate()->pluck('value', 'key')->all();
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
            app(ModerationAudit::class)->record($request->user(), 'settings', 1, 'update_settings', 'Cập nhật thông tin website và nội quy cộng đồng.', $before, $data);
        });

        return back()->with('success', 'Đã cập nhật cài đặt cộng đồng.');
    }
}
