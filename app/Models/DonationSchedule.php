<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonationSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'facility_id',
        'title',
        'event_type',
        'event_date',
        'start_time',
        'end_time',
        'venue',
        'latitude',
        'longitude',
        'description',
        'contact_person',
        'contact_number',
        'is_public',
        'status',
        'start_at',
        'end_at',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'event_date' => 'date',
            'is_public' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function eventRegistrations(): HasMany { return $this->hasMany(EventRegistration::class, 'donation_schedule_id'); }
    public function registeredDonors(): BelongsToMany
    {
        return $this->belongsToMany(
            Donor::class,
            'event_registrations',
            'donation_schedule_id',
            'donor_id'
        )->withPivot(['facility_id', 'status', 'registered_at'])->withTimestamps();
    }

    public function getEventTypeLabelAttribute(): string
    {
        return $this->event_type === 'bloodletting' ? 'Bloodletting' : 'Blood Donation';
    }
}
