<?php

namespace App\Support;

class PdfImage
{
    public static function supported(string $path): bool
    {
        $type = @getimagesize($path)[2] ?? null;

        // JPEG can be embedded directly. Other raster formats need GD support.
        return match ($type) {
            IMAGETYPE_JPEG => true,
            IMAGETYPE_PNG => function_exists('imagecreatefrompng'),
            IMAGETYPE_GIF => function_exists('imagecreatefromgif') && function_exists('imagepng'),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') && function_exists('imagepng'),
            default => false,
        };
    }
}
