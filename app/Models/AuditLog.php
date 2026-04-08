<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id', 'user_id', 'action', 'auditable_type', 'auditable_id', 'details', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
