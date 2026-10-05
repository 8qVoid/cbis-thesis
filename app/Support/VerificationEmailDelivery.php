<?php

namespace App\Support;

use App\Models\User;
use Throwable;

class VerificationEmailDelivery
{
    public const FAILURE_MESSAGE = 'Your account is saved and awaiting email verification. We could not send the email right now. Please try resending shortly.';

    public function send(User $user): bool
    {
        try {
            $user->sendEmailVerificationNotification();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
