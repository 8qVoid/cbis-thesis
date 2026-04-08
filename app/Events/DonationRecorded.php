<?php

namespace App\Events;

use App\Models\DonationRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DonationRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public DonationRecord $record)
    {
    }
}
