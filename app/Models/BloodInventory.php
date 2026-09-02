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

    public const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    public const COMPONENTS = [
        'whole_blood' => 'Whole Blood',
        'packed_red_blood_cells' => 'Packed Red Blood Cells',
        'platelet_concentrate' => 'Platelet Concentrate',
        'fresh_frozen_plasma' => 'Fresh Frozen Plasma',
    ];

    protected $table = 'blood_inventory';

    protected $fillable = [
        'facility_id', 'donation_record_id', 'blood_type', 'component', 'units_available', 'expiration_date', 'status', 'last_low_stock_alert_at',
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
    public function getComponentLabelAttribute(): string { return self::COMPONENTS[$this->component] ?? $this->component; }
}
