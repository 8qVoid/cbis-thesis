<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BloodRelease extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'facility_id', 'blood_inventory_id', 'released_by', 'patient_name', 'requesting_unit', 'released_at',
        'units_released', 'purpose',
    ];

    protected function casts(): array
    {
        return [
            'released_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function inventory(): BelongsTo { return $this->belongsTo(BloodInventory::class, 'blood_inventory_id'); }
    public function releaser(): BelongsTo { return $this->belongsTo(User::class, 'released_by'); }
}
