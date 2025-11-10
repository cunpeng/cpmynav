<?php
// qrcode.php - 修复手机居中显示问题

// 引入配置文件
require_once 'config.php';

// 获取二维码内容
$content = $_GET['content'] ?? '';

// 如果没有内容，显示错误
if (empty($content)) {
    header("HTTP/1.0 404 Not Found");
    echo "二维码内容为空";
    exit;
}

// 正确解码内容
$content = rawurldecode($content);

// 设置内容类型为PNG图片
header('Content-Type: image/png');

// 使用api.qrserver.com生成二维码
$size = isset($_GET['size']) ? intval($_GET['size']) : 300;

// 直接使用解码后的内容
$apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($content);

// 获取二维码图片
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$imageData = @file_get_contents($apiUrl, false, $context);

if ($imageData !== false && strpos($http_response_header[0], '200') !== false) {
    // 直接输出图片数据
    echo $imageData;
} else {
    // 如果API请求失败，尝试使用备选方案
    $fallbackUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . rawurlencode($content);
    $imageData = @file_get_contents($fallbackUrl, false, $context);
    
    if ($imageData !== false && strpos($http_response_header[0], '200') !== false) {
        echo $imageData;
    } else {
        // 生成错误图像
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