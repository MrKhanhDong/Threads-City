<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('blog:admin {email : Email của tác giả} {--name= : Tên hiển thị}')]
#[Description('Tạo tài khoản tác giả bằng mật khẩu nhập ẩn; không nâng quyền tài khoản độc giả có sẵn')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Hãy chạy lệnh tương tác để nhập mật khẩu an toàn; không truyền mật khẩu qua dòng lệnh.');

            return self::FAILURE;
        }

        $email = mb_strtolower(trim((string) $this->argument('email')));
        $name = trim((string) ($this->option('name') ?: $this->ask('Tên hiển thị')));
        $password = $this->secret('Mật khẩu (ít nhất 8 ký tự, có chữ và số)');
        $confirmation = $this->secret('Nhập lại mật khẩu');
        $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation], [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        try {
            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->forceFill(['role' => 'admin', 'email_verified_at' => now()])->save();
        } catch (UniqueConstraintViolationException) {
            $this->error('Email đã tồn tại. Không thay đổi quyền hoặc mật khẩu của tài khoản đó.');

            return self::FAILURE;
        }

        $this->info('Đã tạo tài khoản tác giả. Mật khẩu không được in ra hoặc lưu trong mã nguồn.');

        return self::SUCCESS;
    }
}
