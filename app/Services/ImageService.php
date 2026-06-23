<?php

namespace App\Services;

/**
 * Traitement d'images (port de resizeImage / uploadProductImage du legacy).
 * Conversion WebP via GD, redimensionnement proportionnel.
 */
class ImageService
{
    /**
     * Redimensionne + convertit en WebP. Retourne true si écrit.
     * Si $aspectRatio est fourni (ex. 4/3 pour paysage), l'image est d'abord
     * recadrée au centre à ce ratio avant le redimensionnement.
     */
    public function resizeToWebp(string $sourcePath, string $destPath, int $maxWidth = 1400, int $quality = 88, ?float $aspectRatio = null): bool
    {
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return false;
        }
        [$width, $height, $type] = $info;

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG  => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath),
            IMAGETYPE_GIF  => @imagecreatefromgif($sourcePath),
            default        => false,
        };
        if (! $src) {
            return false;
        }

        // Recadrage centré au ratio cible (ex. paysage 4:3).
        if ($aspectRatio !== null && $aspectRatio > 0 && $height > 0) {
            $currentRatio = $width / $height;
            if (abs($currentRatio - $aspectRatio) > 0.01) {
                if ($currentRatio > $aspectRatio) {
                    $cropW = (int) round($height * $aspectRatio);
                    $cropH = $height;
                } else {
                    $cropW = $width;
                    $cropH = (int) round($width / $aspectRatio);
                }
                $srcX = (int) round(($width - $cropW) / 2);
                $srcY = (int) round(($height - $cropH) / 2);
                $cropped = imagecreatetruecolor($cropW, $cropH);
                imagecopy($cropped, $src, 0, 0, $srcX, $srcY, $cropW, $cropH);
                imagedestroy($src);
                $src = $cropped;
                $width = $cropW;
                $height = $cropH;
            }
        }

        $ratio = $width <= $maxWidth ? 1 : $maxWidth / $width;
        $newWidth = (int) ($width * $ratio);
        $newHeight = (int) ($height * $ratio);

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $ok = imagewebp($dst, $destPath, $quality);
        imagedestroy($src);
        imagedestroy($dst);

        return (bool) $ok;
    }

    /** Décode une image base64 et la sauve en WebP. Retourne le chemin relatif (uploads/...) ou false. */
    public function saveBase64AsWebp(string $base64, string $mimeType, int $productId, int $index, ?float $aspectRatio = null): string|false
    {
        $dir = public_path("uploads/generated/{$productId}");
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return false;
        }

        $ext = match ($mimeType) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        $ts = time();
        $tempPath = "{$dir}/img_{$index}_{$ts}.{$ext}";

        $decoded = base64_decode($base64, true);
        if ($decoded === false || file_put_contents($tempPath, $decoded) === false) {
            return false;
        }

        $webpPath = "{$dir}/img_{$index}_{$ts}.webp";
        if ($this->resizeToWebp($tempPath, $webpPath, 1600, 92, $aspectRatio)) {
            @unlink($tempPath);
            return "uploads/generated/{$productId}/" . basename($webpPath);
        }

        return "uploads/generated/{$productId}/" . basename($tempPath);
    }
}
