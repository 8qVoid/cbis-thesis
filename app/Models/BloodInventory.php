<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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

    public static function lowStockThreshold(string $component): int
    {
        return match ($component) {
            'whole_blood', 'packed_red_blood_cells' => 20,
            'fresh_frozen_plasma' => 10,
            default => 5,
        };
    }

    protected $table = 'blood_inventory';

    protected $fillable = [
        'facility_id', 'donation_record_id', 'blood_type', 'component', 'units_available', 'expiration_date', 'expiration_time', 'status', 'last_low_stock_alert_at', 'showcase_key',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
            'last_low_stock_alert_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function donationRecord(): BelongsTo
    {
        return $this->belongsTo(DonationRecord::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(BloodRelease::class, 'blood_inventory_id');
    }

    public function getComponentLabelAttribute(): string
    {
        return self::COMPONENTS[$this->component] ?? $this->component;
    }

    public function expiresAt(): ?Carbon
    {
        if (! $this->expiration_date) {
            return null;
        }

        return Carbon::parse($this->expiration_date->toDateString().' '.($this->expiration_time ?: '23:59:59'), 'Asia/Manila');
    }

    public function isExpired(): bool
    {
        return $this->expiresAt()?->isPast() ?? false;
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        $now = now('Asia/Manila');

        return $query->where(function (Builder $query) use ($now): void {
            $query->whereDate('expiration_date', '>', $now->toDateString())
                ->orWhere(function (Builder $query) use ($now): void {
                    $query->whereDate('expiration_date', $now->toDateString())
                        ->where(function (Builder $query) use ($now): void {
                            $query->whereNull('expiration_time')->orWhereTime('expiration_time', '>=', $now->format('H:i:s'));
                        });
                });
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        $now = now('Asia/Manila');

        return $query->where(function (Builder $query) use ($now): void {
            $query->whereDate('expiration_date', '<', $now->toDateString())
                ->orWhere(function (Builder $query) use ($now): void {
                    $query->whereDate('expiration_date', $now->toDateString())
                        ->whereNotNull('expiration_time')->whereTime('expiration_time', '<', $now->format('H:i:s'));
                });
        });
    }
}
