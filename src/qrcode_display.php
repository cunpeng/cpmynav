<?php
require_once 'config.php';

$content = $_GET['content'] ?? '';

if (empty($content)) {
    header("HTTP/1.0 404 Not Found");
    echo "二维码内容为空";
    exit;
}

$content = rawurldecode($content);

$pageTitle = "二维码 - " . (strlen($content) > 30 ? substr($content, 0, 30) . "..." : $content);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        html, body { 
            height: 100%; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: #f0f2f5; 
        }
        body { 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
            padding: 20px; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            width: 100%;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .qr-title {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            color: #333;
        }
        .qr-code {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .content-preview {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            max-width: 100%;
            word-break: break-all;
        }
        .back-button {
            margin-top: 2rem;
            padding: 0.8rem 1.5rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            .qr-title {
                font-size: 1.2rem;
                margin-bottom: 1.5rem;
            }
            .qr-code {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="qr-title">二维码</h1>
        
        <img src="qrcode.php?content=<?= urlencode($content) ?>&size=300" alt="二维码" class="qr-code">
        
        <div class="content-preview">
            <strong>内容:</strong><br>
            <?= htmlspecialchars($content) ?>
        </div>
        
        <a href="javascript:history.back()" class="back-button">返回</a>
    </div>
</body>
</html>