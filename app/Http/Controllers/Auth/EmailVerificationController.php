<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DonationSchedule;
use App\Models\EventRegistration;
use App\Support\DonationAgePolicy;
use App\Support\VerificationEmailDelivery;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account.dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();
        $eventMessage = $this->registerPendingEvent($request);

        return redirect()->route('account.dashboard')
            ->with('success', 'Email verified. Your account is now active.'.$eventMessage);
    }

    public function resend(Request $request, VerificationEmailDelivery $verificationEmail): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account.dashboard');
        }

        if (! $verificationEmail->send($request->user())) {
            return redirect()->route('verification.notice')
                ->with('verification_warning', VerificationEmailDelivery::FAILURE_MESSAGE);
        }

        return redirect()->route('verification.notice')
            ->with('verification_warning', null)
            ->with('success', 'A new verification link has been sent to your email. Check your inbox and spam or junk folder.');
    }

    private function registerPendingEvent(Request $request): string
    {
        $user = $request->user();
        $eventId = $user->pending_event_id;
        $user->forceFill(['pending_event_id' => null])->save();
        $donor = $user->donorProfile;
        if (! $eventId || ! $donor) {
            return '';
        }
        if (! DonationAgePolicy::isOldEnough($donor->birth_date)) {
            return ' You can register for donation events when you meet the minimum age requirement.';
        }

        $event = DonationSchedule::query()
            ->where('is_public', true)->where('approval_status', 'approved')
            ->whereDate('event_date', '>=', today())->find($eventId);
        if (! $event?->isRegistrationOpen()) {
            return ' The selected event is no longer open; please choose another event.';
        }

        EventRegistration::query()->updateOrCreate(
            ['donation_schedule_id' => $event->id, 'donor_id' => $donor->id],
            ['facility_id' => $event->facility_id, 'status' => 'registered', 'registered_at' => now()]
        );

        return ' You are registered for the selected event.';
    }
}
