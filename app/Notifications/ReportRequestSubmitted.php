<?php

namespace App\Notifications;

use App\Models\ReportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(public ReportRequest $reportRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Report requested',
            'report_request_id' => $this->reportRequest->id,
            'report_type' => $this->reportRequest->report_type,
            'requester_name' => $this->reportRequest->requester_name,
            'facility_id' => $this->reportRequest->facility_id,
            'status' => 'pending',
        ];
    }
}
