<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacilityApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_name',
        'facility_type',
        'contact_person',
        'contact_number',
        'email',
        'address',
        'doh_accreditation_number',
        'legitimacy_proof_path',
        'doh_accreditation_proof_path',
        'status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'facility_id',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
