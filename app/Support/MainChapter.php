<?php

namespace App\Support;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Builder;

class MainChapter
{
    public static function ids(): Builder
    {
        return Facility::query()->where('is_main_chapter', true)->where('is_active', true)->select('id');
    }

    public static function contains(?int $id): bool
    {
        return $id !== null && self::ids()->whereKey($id)->exists();
    }
}
