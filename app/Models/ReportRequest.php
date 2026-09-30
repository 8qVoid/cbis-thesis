<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRequest extends Model
{
    public const TYPES = [
        'stock_summary' => 'Current blood stock summary',
        'inventory_records' => 'Inventory records',
        'selected_report' => 'Selected report sections',
    ];

    protected $fillable = [
        'facility_id', 'requested_by', 'requester_name', 'report_type', 'status',
        'pending_key', 'reviewed_by', 'reviewer_name', 'reviewed_at', 'review_notes', 'selection', 'snapshot',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'selection' => 'array', 'snapshot' => 'array'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getReportLabelAttribute(): string
    {
        return self::TYPES[$this->report_type] ?? 'Inventory report';
    }
}
