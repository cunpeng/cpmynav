<?php
session_start();
require_once 'config.php';

$healthCheckRetries = 3;
$healthStatus = false;

for ($i = 0; $i < $healthCheckRetries; $i++) {
    if (check_health()) {
        $healthStatus = true;
        break;
    }
    if ($i < $healthCheckRetries - 1) {
        usleep(100000);
    }
}

if (!$healthStatus) {
    header("HTTP/1.1 200 OK");
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>系统维护中</title>
        <meta http-equiv=\"refresh\" content=\"2\">
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { color: #dc3545; font-size: 18px; }
            .retry { color: #666; font-size: 14px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='error'>系统维护中，请稍后再试</div>
        <div class='retry'>2秒后自动重试...</div>
    </body>
    </html>";
    exit;
}

if (isset($_GET['path']) && !empty($_GET['path'])) {
    $path = $_GET['path'];
} elseif (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
    $path = ltrim($_SERVER['PATH_INFO'], '/');
} elseif (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != '/') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = ltrim($uri, '/');
    
    if (strpos($path, 'index.php/') === 0) {
        $path = substr($path, 9);
    }
} else {
    $path = '';
}

$path = preg_replace('/^index\.php\//', '', $path);
$path = trim($path, '/');

if (isset($_GET['debug'])) {
    error_log("Path Debug: GET_path=" . ($_GET['path'] ?? '') . 
              ", PATH_INFO=" . ($_SERVER['PATH_INFO'] ?? '') . 
              ", REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? '') . 
              ", Final_path=" . $path);
}

$data = json_decode(file_get_contents(DATA_FILE), true);
$is_licensed = $data['license_verified'] ?? false;

if (empty($path)) {
    $currentPage = [
        'title' => $data['siteName'] ?? 'CPMYNAV智能导航建站管理系统',
        'links' => $data['links'] ?? []
    ];
    $pageId = 'home';
} else {
    $pageId = $path;
    if (isset($data['pages'][$pageId])) {
        $currentPage = $data['pages'][$pageId];
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "页面不存在";
        exit;
    }
}

require_once 'stats.php';

function generateBreadcrumbs($pageId, $data) {
    $breadcrumbs = [];
    $parts = explode('/', $pageId);
    
    $currentPath = '';
    foreach ($parts as $part) {
        $currentPath = $currentPath ? $currentPath . '/' . $part : $part;
        if (isset($data['pages'][$currentPath])) {
            $breadcrumbs[] = [
                'title' => $data['pages'][$currentPath]['title'],
                'url' => '/' . $currentPath
            ];
        }
    }
    
    array_unshift($breadcrumbs, [
        'title' => $data['siteName'] ?? '首页',
        'url' => '/'
    ]);
    
    return $breadcrumbs;
}

$breadcrumbs = generateBreadcrumbs($pageId, $data);
$siteName = $data['siteName'] ?? 'CPMYNAV智能导航建站管理系统';
$pageTitle = $pageId === 'home' ? $siteName : $currentPage['title'] . ' - ' . $siteName;
$links = $currentPage['links'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title><?= $pageTitle ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        }
        .header { text-align: center; margin: 5px 0; }
        .site-name { 
            color: <?= $data['styleConfig']['siteName']['color'] ?? '#1a1a1a' ?>; 
            font-size: <?= $data['styleConfig']['siteName']['fontSize'] ?? '2.5rem' ?>; 
            margin-bottom: <?= $data['styleConfig']['siteName']['marginBottom'] ?? '15px' ?>; 
        }
        .breadcrumb { 
            margin: 0 0 <?= $data['styleConfig']['breadcrumb']['marginBottom'] ?? '15px' ?>; 
            text-align: center; 
        }
        .breadcrumb a { color: #007bff; text-decoration: none; }
        .breadcrumb span { 
            margin: 0 5px; 
            color: <?= $data['styleConfig']['breadcrumb']['color'] ?? '#666' ?>; 
            font-size: <?= $data['styleConfig']['breadcrumb']['fontSize'] ?? '14px' ?>; 
        }
        

        @media (max-width: 768px) {
            .site-name { 
                font-size: <?= $data['styleConfig']['siteName']['fontSize'] ?? '2.5rem' ?>;
            }
            .breadcrumb span { 
                font-size: <?= $data['styleConfig']['breadcrumb']['fontSize'] ?? '14px' ?>;
            }
            .stats-footer {
                font-size: <?= $data['styleConfig']['stats']['fontSize'] ?? '14px' ?>;
            }
            .copyright-footer {
                font-size: <?= $data['styleConfig']['copyright']['fontSize'] ?? '14px' ?>;
            }
        }
        .nav-list { display: grid; grid-template-columns: repeat(<?= $data['linksPerRow'] ?? 2 ?>, 1fr); gap: 15px; }
        .nav-item { 
            background: #fff; 
            padding: 20px; 
            border-radius: 12px; 
            transition: transform 0.2s; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
            margin: <?= $data['styleConfig']['linkItem']['margin'] ?? '0px' ?>; 
            min-height: 64px;
            display: flex;
            align-items: center;
        }
        .nav-item:hover { transform: translateY(-2px); }
        .nav-item a { 
            text-decoration: none; 
            color: <?= $data['styleConfig']['linkItem']['color'] ?? '#1a1a1a' ?>; 
            display: flex; 
            align-items: center; 
            justify-content: flex-start;
            font-size: <?= $data['styleConfig']['linkItem']['fontSize'] ?? '16px' ?>; 
            line-height: 1.2;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            width: 100%;
            height: 100%;
        }
        .nav-item img { 
            width: 24px; 
            height: 24px; 
            margin-right: 10px; 
            object-fit: contain;
        }
        
        .footer-wrapper {
            margin-top: auto;
        }
        .stats-footer {
            text-align: center;
            margin-top: <?= $data['styleConfig']['stats']['marginTop'] ?? '0px' ?>;
            padding: 15px;
            color: <?= $data['styleConfig']['stats']['color'] ?? '#666' ?>;
            font-size: <?= $data['styleConfig']['stats']['fontSize'] ?? '14px' ?>;
            border-top: 1px solid #eee;
        }
        .copyright-footer {
            text-align: center;
            margin-top: <?= $data['styleConfig']['copyright']['marginTop'] ?? '0px' ?>;
            padding: 10px;
            color: <?= $data['styleConfig']['copyright']['color'] ?? '#666' ?>;
            font-size: <?= $data['styleConfig']['copyright']['fontSize'] ?? '14px' ?>;
            line-height: 1.5;
        }
        @media (max-width: 480px) {
            .site-name { font-size: <?= $data['styleConfig']['siteName']['fontSize'] ?? '2.5rem' ?>; }
            .nav-list { grid-template-columns: 1fr; gap: 10px; }
            .nav-item { 
                padding: 0;
                min-height: 60px;
                display: flex;
                align-items: center;
                justify-content: flex-start;
            }
            .nav-item a { 
                font-size: 16px; 
                line-height: 1.3;
                display: flex;
                align-items: center;
                justify-content: flex-start;
                width: 100%;
                text-align: left;
                word-wrap: break-word;
                word-break: break-word;
                overflow-wrap: break-word;
                white-space: normal;
                height: 100%;
                padding: 12px 18px;
            }
            .nav-item img { 
                width: 18px; 
                height: 18px; 
                margin-right: 6px;
            }
            .stats-footer { font-size: <?= $data['styleConfig']['stats']['fontSize'] ?? '14px' ?>; }
            .copyright-footer { font-size: <?= $data['styleConfig']['copyright']['fontSize'] ?? '14px' ?>; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="site-name"><?= htmlspecialchars($pageId === 'home' ? $siteName : $currentPage['title']) ?></h1>
        </div>
        
        <?php if (($data['showBreadcrumb'] ?? true) && $pageId !== 'home'): ?>
        <div class="breadcrumb">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?>
                    <span>/</span>
                <?php endif; ?>
                <?php if ($index < count($breadcrumbs) - 1): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['title']) ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars($crumb['title']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="nav-list">
            <?php foreach ($links as $link): ?>
                <?php 
                $isVisible = !isset($link['visible']) || $link['visible'];
                if (!$isVisible) continue;
                
                $linkStyle = $link['style'] ?? [];
                $color = $linkStyle['color'] ?? '#1a1a1a';
                ?>
                
                <div class="nav-item">
                    <?php 
                    $target = '';
                    $url = $link['url'];
                    
            if (isset($link['isPage']) && $link['isPage']) {
                $target = '';
            } elseif (isset($link['isQRCode']) && $link['isQRCode']) {
                $target = '';
                if (!empty($link['qrcodeContent'])) {
                    $url = "/qrcode_display.php?content=" . rawurlencode($link['qrcodeContent']);
                }
            } else {
                        $target = 'target="_blank"';
                    }
                    ?>
                    <a href="<?= htmlspecialchars($url) ?>" <?= $target ?> style="color: <?= $color ?>;">
                        <?php if(!empty($link['icon'])): ?>
                            <img src="<?= htmlspecialchars($link['icon']) ?>" alt="图标" style="max-width: 24px; max-height: 24px; object-fit: contain;">
                        <?php endif; ?>
                        <?= htmlspecialchars($link['name']) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="footer-wrapper">
            <?php if ($data['showStats'] ?? true): ?>
            <div class="stats-footer">
                总访问量：<?= $totalVisits ?> 
                今日访问：<?= $todayVisits ?>
                <?php if(date('H') < 6): ?>🌙 夜深了<?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['footerCopyright'])): ?>
            <div class="copyright-footer">
                <?= $data['footerCopyright'] ?>
            </div>
            <?php endif; ?>
                        
    <?php if(!$is_licensed): ?>
    <div class="form-box" style="text-align: center; color: #666; font-size: 14px;">
        <a href="https://github.com/cunpeng/cpmynav" target="_blank" style="text-decoration: none; color: #007bff;">CPMYNAV智能导航建站管理系统</a>
    </div>
    <?php endif; ?>
        </div>
    </div>
</body>
</html>