<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BloodBankLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'facility_id', 'latitude', 'longitude', 'address', 'contact_number', 'photo_path',
    ];

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
}
