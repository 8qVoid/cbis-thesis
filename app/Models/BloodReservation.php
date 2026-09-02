<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloodReservation extends Model
{
    public const STATUSES = ['submitted', 'under_review', 'approved', 'rejected', 'fulfilled', 'cancelled'];

    protected $fillable = [
        'reference', 'patient_user_id', 'facility_id', 'blood_type', 'component', 'units_requested',
        'needed_on', 'clinical_purpose', 'status', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array { return ['needed_on' => 'date', 'reviewed_at' => 'datetime']; }
    public function patient(): BelongsTo { return $this->belongsTo(User::class, 'patient_user_id'); }
    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function documents(): HasMany { return $this->hasMany(BloodReservationDocument::class); }
}
