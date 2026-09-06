<?php

namespace Tests\Unit;

use App\Models\BloodReservation;
use App\Models\DonationSchedule;
use App\Models\Facility;
use App\Models\User;
use App\Notifications\BloodReservationStatusChanged;
use App\Notifications\EventPostedNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountNotificationTest extends TestCase
{
    #[Test]
    public function patient_reservation_updates_are_saved_and_emailed(): void
    {
        $reservation = new BloodReservation([
            'reference' => 'BR-TEST-001',
            'status' => 'approved',
            'review_notes' => 'Test approval.',
        ]);
        $notification = new BloodReservationStatusChanged($reservation);

        $this->assertSame(['database', 'mail'], $notification->via(new User()));
        $this->assertSame('BR-TEST-001', $notification->toArray(new User())['reference']);
        $this->assertSame('approved', $notification->toArray(new User())['status']);
    }

    #[Test]
    public function donor_event_updates_are_saved_and_emailed(): void
    {
        $event = new DonationSchedule([
            'title' => 'Community Blood Drive',
            'event_type' => 'blood_donation',
            'facility_id' => 1,
        ]);
        $event->setRelation('facility', new Facility(['name' => 'Bacolod Main Chapter']));
        $notification = new EventPostedNotification($event);

        $this->assertSame(['database', 'mail'], $notification->via(new User()));
        $this->assertSame('Community Blood Drive', $notification->toArray(new User())['event_title']);
        $this->assertSame(1, $notification->toArray(new User())['facility_id']);
    }
}
