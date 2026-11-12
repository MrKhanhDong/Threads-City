<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ReportHandled extends Notification
{
    public function __construct(public int $reportId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['report_id' => $this->reportId];
    }
}
