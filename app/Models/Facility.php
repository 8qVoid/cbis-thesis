<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facility extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'contact_person',
        'contact_number',
        'email',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function donors(): HasMany { return $this->hasMany(Donor::class); }
    public function donationRecords(): HasMany { return $this->hasMany(DonationRecord::class); }
    public function bloodlettingRecords(): HasMany { return $this->hasMany(BloodlettingRecord::class); }
    public function inventories(): HasMany { return $this->hasMany(BloodInventory::class); }
    public function bloodReleases(): HasMany { return $this->hasMany(BloodRelease::class); }
    public function schedules(): HasMany { return $this->hasMany(DonationSchedule::class); }
    public function events(): HasMany { return $this->hasMany(DonationSchedule::class); }
    public function eventRegistrations(): HasMany { return $this->hasMany(EventRegistration::class); }
    public function locations(): HasMany { return $this->hasMany(BloodBankLocation::class); }
}
