<?php

namespace App\Support;

class PhotoUpload
{
    public const MAX_MB = 10;

    public const MAX_FILES = 10;

    public static function rules(int $maxMb = self::MAX_MB): array
    {
        return ['image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:'.($maxMb * 1024)];
    }

    public static function messages(string $field, int $maxMb = self::MAX_MB): array
    {
        $each = str_ends_with($field, '.*') ? 'Each photo' : 'The photo';

        return [
            "{$field}.max" => "{$each} must be {$maxMb} MB or smaller. Please choose a smaller photo.",
            "{$field}.uploaded" => "{$each} could not be uploaded. It may be larger than {$maxMb} MB.",
            "{$field}.image" => 'Please select a JPG/JPEG or PNG photo.',
            "{$field}.mimes" => 'Please select a JPG/JPEG or PNG photo.',
            "{$field}.extensions" => 'Please select a JPG/JPEG or PNG photo.',
        ];
    }

    // PHP discards the whole request above post_max_size, so the browser checks totals against it.
    public static function maxRequestBytes(): int
    {
        $value = trim((string) ini_get('post_max_size'));
        $number = (int) $value;

        return match (strtoupper(substr($value, -1))) {
            'G' => $number * 1024 ** 3,
            'M' => $number * 1024 ** 2,
            'K' => $number * 1024,
            default => $number,
        };
    }
}
