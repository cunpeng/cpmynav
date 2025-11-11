<?php
// index.php
session_start();
// 引入配置文件
require_once 'config.php';

// 获取请求的路径
$path = isset($_GET['path']) ? $_GET['path'] : '';

// 加载导航数据
$data = json_decode(file_get_contents(DATA_FILE), true);

// 检查授权状态
$is_licensed = $data['license_verified'] ?? false;

// 确定当前页面
if (empty($path)) {
    // 首页
    $currentPage = [
        'title' => $data['siteName'] ?? '我的导航站',
        'links' => $data['links'] ?? []
    ];
    $pageId = 'home';
} else {
    // 子页面
    $pageId = $path;
    if (isset($data['pages'][$pageId])) {
        $currentPage = $data['pages'][$pageId];
    } else {
        // 页面不存在，显示404
        header("HTTP/1.0 404 Not Found");
        echo "页面不存在";
        exit;
    }
}

// 加载访客统计
require_once 'stats.php';

// 生成面包屑导航
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
    
    // 添加首页
    array_unshift($breadcrumbs, [
        'title' => $data['siteName'] ?? '首页',
        'url' => '/'
    ]);
    
    return $breadcrumbs;
}

$breadcrumbs = generateBreadcrumbs($pageId, $data);
$siteName = $data['siteName'] ?? '我的导航站';
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
            flex: 1; /* 这个属性确保内容区域占据剩余空间 */
            display: flex;
            flex-direction: column;
        }
        .header { text-align: center; margin: 5px 0; }
        .site-name { color: #1a1a1a; font-size: 2.5rem; margin-bottom: 10px; }
        .breadcrumb { margin: 0 0 15px; text-align: center; }
        .breadcrumb a { color: #007bff; text-decoration: none; }
        .breadcrumb span { margin: 0 5px; color: #666; }
        .nav-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .nav-item { background: #fff; padding: 20px; border-radius: 12px; transition: transform 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .nav-item:hover { transform: translateY(-2px); }
        .nav-item a { text-decoration: none; color: #1a1a1a; display: flex; align-items: center; }
        .nav-item img { width: 24px; height: 24px; margin-right: 10px; }
        
        /* 只修改底部区域 - 确保始终在底部 */
        .footer-wrapper {
            margin-top: auto; /* 这个属性将底部信息推到最下面 */
        }
        .stats-footer {
            text-align: center;
            margin-top: 0px;
            padding: 15px;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #eee;
        }
        .copyright-footer {
            text-align: center;
            margin-top: -12px;
            padding: 10px;
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        @media (max-width: 480px) {
            .site-name { font-size: 2rem; }
            .nav-list { grid-template-columns: 1fr; }
            .stats-footer { font-size: 12px; }
            .copyright-footer { font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="site-name"><?= htmlspecialchars($pageId === 'home' ? $siteName : $currentPage['title']) ?></h1>
        </div>
        
                <!-- 面包屑导航：根据后台设置显示 -->
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
        
        <!-- 在index.php中找到导航列表部分，更新二维码链接 -->
        <div class="nav-list">
            <?php foreach ($links as $link): ?>
                <?php 
                // 新增：检查链接是否可见（默认可见）
                $isVisible = !isset($link['visible']) || $link['visible'];
                if (!$isVisible) continue;
                ?>
                
                <div class="nav-item">
                    <?php 
                    // 确定链接目标和URL
                    $target = '';
                    $url = $link['url'];
                    
            if (isset($link['isPage']) && $link['isPage']) {
                // 子页面链接
                $target = '';
            } elseif (isset($link['isQRCode']) && $link['isQRCode']) {
                // 二维码链接 - 使用绝对路径
                $target = '';
                if (!empty($link['qrcodeContent'])) {
                    // 使用绝对路径，确保在任何子页面都能正确访问
                    $url = "/qrcode_display.php?content=" . rawurlencode($link['qrcodeContent']);
                }
            } else {
                // 外部链接
                        $target = 'target="_blank"';
                    }
                    ?>
                    <a href="<?= htmlspecialchars($url) ?>" <?= $target ?>>
                        <?php if(!empty($link['icon'])): ?>
                            <img src="<?= htmlspecialchars($link['icon']) ?>" alt="图标">
                        <?php endif; ?>
                        <?= htmlspecialchars($link['name']) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 底部信息包装器 -->
        <div class="footer-wrapper">
            <!-- 统计信息展示 -->
            <?php if ($data['showStats'] ?? true): ?>
            <div class="stats-footer">
                总访问量：<?= $totalVisits ?> 
                今日访问：<?= $todayVisits ?>
                <?php if(date('H') < 6): ?>🌙 夜深了<?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- 版权信息展示 -->
            <?php if (!empty($data['footerCopyright'])): ?>
            <div class="copyright-footer">
                <?= $data['footerCopyright'] ?>
            </div>
            <?php endif; ?>
            <!-- 固定的作者信息wucunpeng - 开源项目防伪标识 -->
    <!-- 自定义代码：只有在未授权时显示 -->
    <?php if(!$is_licensed): ?>
    <div class="form-box" style="text-align: center; color: #666; font-size: 14px;">
        <a href="https://github.com/cunpeng/cpmynav" target="_blank" style="text-decoration: none; color: #007bff;">GitHub</a>
    </div>
    <?php endif; ?>
    </div>
</body>
</html>