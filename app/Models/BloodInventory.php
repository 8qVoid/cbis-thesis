<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BloodInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'blood_inventory';

    protected $fillable = [
        'facility_id', 'donation_record_id', 'blood_type', 'units_available', 'expiration_date', 'status', 'last_low_stock_alert_at',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
            'last_low_stock_alert_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function donationRecord(): BelongsTo { return $this->belongsTo(DonationRecord::class); }
    public function releases(): HasMany { return $this->hasMany(BloodRelease::class); }
}
