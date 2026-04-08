<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonationRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'facility_id', 'donor_id', 'recorded_by', 'donation_no', 'donated_at', 'blood_type',
        'volume_ml', 'expiration_date', 'status', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'donated_at' => 'datetime',
            'expiration_date' => 'date',
        ];
    }

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function donor(): BelongsTo { return $this->belongsTo(Donor::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function inventory(): HasOne { return $this->hasOne(BloodInventory::class); }
    public function bloodlettingRecord(): HasOne { return $this->hasOne(BloodlettingRecord::class); }
}
