<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloodReservationDocument extends Model
{
    protected $fillable = ['blood_reservation_id', 'type', 'path', 'original_name', 'mime_type', 'size'];
    public function reservation(): BelongsTo { return $this->belongsTo(BloodReservation::class, 'blood_reservation_id'); }
}
