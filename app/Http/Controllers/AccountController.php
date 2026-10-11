<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit(Request $request) { return view('account.edit', ['user' => $request->user()]); }
    public function update(Request $request)
    {
        if (is_string($request->input('email'))) $request->merge(['email' => mb_strtolower($request->input('email'))]);
        $data = $request->validate([
            'name' => 'required|string|max:80', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'current_password' => 'nullable|required_with:password|current_password:web',
            'password' => ['nullable', 'string', 'max:255', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);
        $request->user()->fill(['name' => $data['name'], 'email' => mb_strtolower($data['email'])]);
        if (!empty($data['password'])) $request->user()->password = $data['password'];
        $request->user()->save();
        $request->session()->regenerate();
        return back()->with('success', 'Đã cập nhật tài khoản.');
    }
}
