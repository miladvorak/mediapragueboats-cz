<?php
/**
 * Resize uploaded photos to email-friendly JPEGs using GD.
 */

declare(strict_types=1);

function pb_gd_available(): bool
{
    return extension_loaded('gd') && function_exists('imagecreatetruecolor');
}

/**
 * Resize an uploaded image file to a JPEG no larger than $maxEdge on its
 * longest side. Returns binary JPEG data, or null if the format is unsupported.
 */
function pb_resize_to_jpeg(string $srcPath, int $maxEdge = 1280, int $quality = 80): ?string
{
    if (!pb_gd_available()) {
        return null;
    }
    $info = @getimagesize($srcPath);
    if ($info === false) {
        return null;
    }
    [$w, $h] = $info;
    $type = $info[2];

    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = @imagecreatefromjpeg($srcPath);
            break;
        case IMAGETYPE_PNG:
            $src = @imagecreatefrompng($srcPath);
            break;
        case IMAGETYPE_GIF:
            $src = @imagecreatefromgif($srcPath);
            break;
        case IMAGETYPE_WEBP:
            $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false;
            break;
        default:
            $src = false;
    }
    if (!$src) {
        return null;
    }

    // Honour EXIF orientation for JPEGs from phones/cameras.
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($srcPath);
        if (!empty($exif['Orientation'])) {
            switch ((int) $exif['Orientation']) {
                case 3: $src = imagerotate($src, 180, 0); break;
                case 6: $src = imagerotate($src, -90, 0); [$w, $h] = [$h, $w]; break;
                case 8: $src = imagerotate($src, 90, 0);  [$w, $h] = [$h, $w]; break;
            }
        }
    }

    $scale = min(1.0, $maxEdge / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $dst = imagecreatetruecolor($nw, $nh);
    // Flatten transparency onto white (JPEG has no alpha).
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    ob_start();
    imagejpeg($dst, null, $quality);
    $data = ob_get_clean();

    // imagedestroy() is a no-op (and deprecated) on PHP 8+; only needed on 7.x.
    if (PHP_VERSION_ID < 80000) {
        imagedestroy($src);
        imagedestroy($dst);
    }

    return $data !== false ? $data : null;
}
