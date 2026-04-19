<?php

namespace App\Http\Controllers;

use App\Models\Subdivision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BrandingController extends Controller
{
    public function favicon(Request $request): Response
    {
        $subdivision = null;

        if (Schema::hasTable('subdivisions')) {
            $subdivision = $request->user()?->subdivision_id
                ? Subdivision::find($request->user()->subdivision_id)
                : null;

            $subdivision ??= Subdivision::query()
                ->where('status', 'Active')
                ->orderBy('subdivision_name')
                ->first()
                ?? Subdivision::query()->orderBy('subdivision_name')->first();
        }

        $sourcePath = null;

        if ($subdivision?->logo_path && Storage::disk('public')->exists($subdivision->logo_path)) {
            $sourcePath = Storage::disk('public')->path($subdivision->logo_path);
        } else {
            $fallback = public_path('imgsrc/logo.png');
            if (is_file($fallback)) {
                $sourcePath = $fallback;
            }
        }

        if (!$sourcePath) {
            abort(404);
        }

        $raw = @file_get_contents($sourcePath);
        if ($raw === false) {
            abort(404);
        }

        $src = @imagecreatefromstring($raw);
        if (!$src) {
            abort(404);
        }

        $size = 64;
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $cropSize = min($srcW, $srcH);
        $cropX = (int) floor(($srcW - $cropSize) / 2);
        $cropY = (int) floor(($srcH - $cropSize) / 2);

        // Center-crop to square first so logos with padding/background fill the favicon better.
        imagecopyresampled($canvas, $src, 0, 0, $cropX, $cropY, $size, $size, $cropSize, $cropSize);

        $radius = ($size / 2) - 0.5;
        $center = $size / 2;

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                $dx = $x - $center;
                $dy = $y - $center;
                if (($dx * $dx) + ($dy * $dy) > ($radius * $radius)) {
                    imagesetpixel($canvas, $x, $y, $transparent);
                }
            }
        }

        ob_start();
        imagepng($canvas);
        $png = ob_get_clean();

        imagedestroy($src);
        imagedestroy($canvas);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
