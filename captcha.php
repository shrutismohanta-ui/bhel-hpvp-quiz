<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Clean & Human-Friendly CAPTCHA Image Generator
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate 5-character clear code if missing or requested
if (empty($_SESSION['captcha_code']) || isset($_GET['new'])) {
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $captchaCode = '';
    for ($i = 0; $i < 5; $i++) {
        $captchaCode .= $chars[rand(0, strlen($chars) - 1)];
    }
    $_SESSION['captcha_code'] = $captchaCode;
}

$code = $_SESSION['captcha_code'];

// Image dimensions
$width = 170;
$height = 46;

// Create image canvas
$image = imagecreatetruecolor($width, $height);

// Color palette
$bgColor = imagecolorallocate($image, 15, 44, 89);      // Dark BHEL Navy
$borderColor = imagecolorallocate($image, 255, 193, 7);  // BHEL Gold Border

// Fill background
imagefill($image, 0, 0, $bgColor);
imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

// Add 2 soft background lines for light anti-bot protection
$faintLineColor = imagecolorallocate($image, 30, 75, 120);
imageline($image, 10, 12, $width - 10, 34, $faintLineColor);
imageline($image, 10, 34, $width - 10, 12, $faintLineColor);

// Add a few subtle background pixels (30 dots)
for ($i = 0; $i < 30; $i++) {
    $dotColor = imagecolorallocate($image, rand(40, 100), rand(80, 150), rand(120, 200));
    imagesetpixel($image, rand(5, $width - 5), rand(5, $height - 5), $dotColor);
}

// Text colors (bright, high-contrast, easy to read)
$textColors = [
    imagecolorallocate($image, 255, 193, 7),   // Bright Gold
    imagecolorallocate($image, 0, 210, 255),   // Cyan
    imagecolorallocate($image, 255, 255, 255), // Pure White
    imagecolorallocate($image, 110, 231, 183), // Soft Green
];

// Render characters clearly
$charWidth = ($width - 30) / strlen($code);

for ($i = 0; $i < strlen($code); $i++) {
    $char = $code[$i];
    $color = $textColors[$i % count($textColors)];
    $x = 18 + ($i * $charWidth);
    $y = 13; // Fixed clean vertical alignment
    
    // Draw crisp large GD font (font size 5)
    imagestring($image, 5, (int)$x, (int)$y, $char, $color);
}

// Disable Caching Headers
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

imagepng($image);
imagedestroy($image);
