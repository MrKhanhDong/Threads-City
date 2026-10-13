<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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
        $data = $request->validate(['site_name' => 'required|string|max:100', 'site_tagline' => 'required|string|max:200', 'site_description' => 'required|string|max:320', 'about' => 'nullable|string|max:10000', 'contact_email' => 'nullable|email|max:255']);
        DB::transaction(function () use ($data): void {
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        return back()->with('success', 'Đã cập nhật thông tin blog.');
    }
}
