<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonorScreening extends Model
{
    protected $fillable = ['donor_id', 'reviewed_by', 'status', 'donor_message', 'review_on'];

    protected function casts(): array
    {
        return ['review_on' => 'date'];
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
