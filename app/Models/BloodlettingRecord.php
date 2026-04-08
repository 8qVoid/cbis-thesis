<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BloodlettingRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'facility_id', 'donation_record_id', 'medical_technologist_id', 'bloodletting_at',
        'verification_status', 'findings', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'bloodletting_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function donationRecord(): BelongsTo { return $this->belongsTo(DonationRecord::class); }
    public function technologist(): BelongsTo { return $this->belongsTo(User::class, 'medical_technologist_id'); }
}
