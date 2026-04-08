<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Donor extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'facility_id', 'first_name', 'last_name', 'middle_name', 'birth_date', 'sex', 'blood_type',
        'contact_number', 'email', 'address', 'is_eligible', 'password', 'is_online_registered',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_eligible' => 'boolean',
            'is_online_registered' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function homeFacility(): BelongsTo { return $this->belongsTo(Facility::class, 'facility_id'); }
    public function donationRecords(): HasMany { return $this->hasMany(DonationRecord::class); }
    public function eventRegistrations(): HasMany { return $this->hasMany(EventRegistration::class); }
    public function registeredEvents(): BelongsToMany
    {
        return $this->belongsToMany(
            DonationSchedule::class,
            'event_registrations',
            'donor_id',
            'donation_schedule_id'
        )->withPivot(['facility_id', 'status', 'registered_at'])->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->last_name.', '.$this->first_name.' '.$this->middle_name);
    }
}
