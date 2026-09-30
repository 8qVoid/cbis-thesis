<?php

namespace App\Notifications;

use App\Models\ReportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportRequestReviewed extends Notification
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
            'title' => 'Report '.$this->reportRequest->status,
            'report_request_id' => $this->reportRequest->id,
            'report_type' => $this->reportRequest->report_type,
            'requester_name' => $this->reportRequest->requester_name,
            'facility_id' => $this->reportRequest->facility_id,
            'status' => $this->reportRequest->status,
            'review_notes' => $this->reportRequest->review_notes,
        ];
    }
}
