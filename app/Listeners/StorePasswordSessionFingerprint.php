<?php

namespace App\Listeners;

use App\Support\PasswordSessions;
use Illuminate\Auth\Events\Login;

class StorePasswordSessionFingerprint
{
    public function handle(Login $event): void
    {
        if (in_array($event->guard, PasswordSessions::GUARDS, true)) {
            PasswordSessions::remember(app('session.store'), $event->guard, $event->user);
        }
    }
}
