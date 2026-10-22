<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportBatch
{
    public const SIZE = 100;

    public static function validate(Request $request): void
    {
        $request->validate([
            'report_part' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'report_max_id' => ['nullable', 'integer', 'min:0'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
        ]);
    }

    public static function apply(Builder $query, Request $request): Builder
    {
        return $query->offset((max(1, $request->integer('report_part', 1)) - 1) * self::SIZE)->limit(self::SIZE);
    }

    public static function suffix(Request $request): string
    {
        return $request->has('report_part') ? '-part-'.$request->integer('report_part', 1) : '';
    }
}
