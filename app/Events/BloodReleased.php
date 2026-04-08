<?php

namespace App\Events;

use App\Models\BloodRelease;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BloodReleased
{
    use Dispatchable, SerializesModels;

    public function __construct(public BloodRelease $release)
    {
    }
}
