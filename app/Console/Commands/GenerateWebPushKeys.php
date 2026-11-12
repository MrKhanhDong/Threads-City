<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

#[Signature('webpush:keys {--force : Thay khóa hiện có; thiết bị cần đăng ký lại}')]
#[Description('Tạo khóa VAPID và lưu vào .env, không hiển thị khóa bí mật')]
class GenerateWebPushKeys extends Command
{
    public function handle(): int
    {
        if (! is_file($this->laravel->environmentFilePath())) {
            $this->error('Tạo tệp .env trước khi tạo khóa Web Push.');

            return self::FAILURE;
        }
        if (filled(config('services.webpush.private_key')) && ! $this->option('force')) {
            $this->error('Đã có khóa Web Push. Chỉ dùng --force khi bạn muốn thay khóa.');

            return self::FAILURE;
        }
        try {
            $keys = VAPID::createVapidKeys();
        } catch (\RuntimeException) {
            $this->error('OpenSSL chưa tạo được khóa. Trên Windows, đặt biến môi trường OPENSSL_CONF tới tệp openssl.cnf của PHP trước khi chạy lệnh.');

            return self::FAILURE;
        }
        $path = $this->laravel->environmentFilePath();
        $content = file_get_contents($path);
        foreach (['WEBPUSH_PUBLIC_KEY' => $keys['publicKey'], 'WEBPUSH_PRIVATE_KEY' => $keys['privateKey']] as $name => $value) {
            $content = preg_match('/^'.$name.'=/m', $content) ? preg_replace('/^'.$name.'=.*$/m', $name.'='.$value, $content) : rtrim($content)."\n".$name.'='.$value."\n";
        }
        file_put_contents($path, $content);
        $this->info('Đã lưu khóa VAPID vào .env. Đặt WEBPUSH_SUBJECT và xóa cache cấu hình trước khi bật Push.');

        return self::SUCCESS;
    }
}
