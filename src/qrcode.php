<?php
require_once 'config.php';

$content = $_GET['content'] ?? '';

if (empty($content)) {
    header("HTTP/1.0 404 Not Found");
    echo "二维码内容为空";
    exit;
}

$content = rawurldecode($content);

header('Content-Type: image/png');

$size = isset($_GET['size']) ? intval($_GET['size']) : 300;

$apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($content);
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$imageData = @file_get_contents($apiUrl, false, $context);

if ($imageData !== false && strpos($http_response_header[0], '200') !== false) {
    echo $imageData;
} else {
    $fallbackUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . rawurlencode($content);
    $imageData = @file_get_contents($fallbackUrl, false, $context);
    
    if ($imageData !== false && strpos($http_response_header[0], '200') !== false) {
        echo $imageData;
    } else {
        $im = imagecreate(300, 150);
        $white = imagecolorallocate($im, 255, 255, 255);
        $red = imagecolorallocate($im, 255, 0, 0);
        $black = imagecolorallocate($im, 0, 0, 0);
        
        imagefill($im, 0, 0, $white);
        imagestring($im, 3, 10, 30, "二维码生成失败", $red);
        imagestring($im, 2, 10, 60, "原始内容:", $black);
        imagestring($im, 2, 10, 80, substr($content, 0, 40), $black);
        imagestring($im, 2, 10, 100, "长度: " . strlen($content), $black);
        imagestring($im, 2, 10, 120, "请检查内容格式", $black);
        imagepng($im);
        imagedestroy($im);
    }
}
?>