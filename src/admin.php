<?php
// 引入配置文件
require_once 'config.php';

session_start();
$data = json_decode(file_get_contents(DATA_FILE), true);

// 从数据文件中获取账号和密码，如果没有则使用默认值
$adminUsername = $data['adminUsername'] ?? 'admin';
$adminPassword = $data['adminPassword'] ?? '12345678';

// 处理账号设置修改
if(isset($_POST['change_account'])) {
    $current_username = $_POST['current_username'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_username = $_POST['new_username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证当前账号密码
    if($current_username === $adminUsername && $current_password === $adminPassword) {
        $hasChanges = false;
        
        // 处理用户名修改
        if(!empty($new_username) && $new_username !== $adminUsername) {
            $data['adminUsername'] = $new_username;
            $adminUsername = $new_username;
            $hasChanges = true;
        }
        
        // 处理密码修改
        if(!empty($new_password)) {
            if($new_password === $confirm_password) {
                $data['adminPassword'] = $new_password;
                $adminPassword = $new_password;
                $hasChanges = true;
            } else {
                $accountError = "新密码不匹配";
            }
        }
        
        // 保存修改
        if($hasChanges && empty($accountError)) {
            file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $accountChanged = true;
        }
    } else {
        $accountError = "当前账号或密码错误";
    }
}

// 处理删除子页面请求
if(isset($_GET['delete_page']) && isset($_GET['page_id'])) {
    $pageToDelete = $_GET['page_id'];
    
    // 从所有页面中删除对该页面的引用
    function removePageReferences(&$data, $pageId) {
        // 处理首页链接
        if(isset($data['links'])) {
            foreach($data['links'] as $index => $link) {
                if(isset($link['isPage']) && $link['isPage'] && ltrim($link['url'], '/') === $pageId) {
                    unset($data['links'][$index]);
                }
            }
            $data['links'] = array_values($data['links']);
        }
        
        // 处理其他页面的链接
        if(isset($data['pages'])) {
            foreach($data['pages'] as $id => $page) {
                if(isset($page['links'])) {
                    foreach($page['links'] as $index => $link) {
                        if(isset($link['isPage']) && $link['isPage'] && ltrim($link['url'], '/') === $pageId) {
                            unset($data['pages'][$id]['links'][$index]);
                        }
                    }
                    $data['pages'][$id]['links'] = array_values($data['pages'][$id]['links']);
                }
            }
        }
        
        // 删除页面本身
        if(isset($data['pages'][$pageId])) {
            unset($data['pages'][$pageId]);
        }
    }
    
    removePageReferences($data, $pageToDelete);
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // 重定向到首页
    header('Location: admin.php?page=home');
    exit();
}

// 登出处理
if(isset($_GET['logout'])) {
    unset($_SESSION['loggedin']);
}

// 登录验证
if(!isset($_SESSION['loggedin'])) {
    if(isset($_POST['username']) && isset($_POST['password'])) {
        if($_POST['username'] === $adminUsername && $_POST['password'] === $adminPassword) {
            $_SESSION['loggedin'] = true;
            // 重定向到请求的页面或首页
            $page = isset($_GET['page']) ? '?page=' . urlencode($_GET['page']) : '';
            header('Location: admin.php' . $page);
            exit();
        } else {
            $error = '账号或密码错误';
        }
    }
    // 显示登录界面
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>后台登录</title>
        <style>
            body { display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f0f2f5; }
            .login-box { background: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 95%; max-width: 400px; }
            .form-group { margin-bottom: 1rem; }
            input { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
            button { background: #007bff; color: white; border: none; padding: 0.8rem; border-radius: 8px; width: 100%; cursor: pointer; }
            .error { color: red; margin-bottom: 1rem; text-align: center; }
            .success { color: green; margin-bottom: 1rem; text-align: center; }
            /* 在现有的CSS样式中添加以下代码 */

/* 拖拽容器优化 */
.sortable-links {
    max-height: 60vh; /* 容器最大高度为视口的60% */
    overflow-y: auto; /* 启用垂直滚动 */
    overflow-x: hidden; /* 隐藏水平滚动 */
    padding: 10px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 15px;
    scroll-behavior: smooth; /* 平滑滚动 */
}

/* 拖拽时的边缘提示 */
.sortable-links.drag-active::before,
.sortable-links.drag-active::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    height: 30px;
    pointer-events: none;
    opacity: 0.5;
    z-index: 10;
    transition: opacity 0.2s ease;
}

.sortable-links.drag-active::before {
    top: 0;
    background: linear-gradient(to bottom, rgba(0,123,255,0.1) 0%, transparent 100%);
}

.sortable-links.drag-active::after {
    bottom: 0;
    background: linear-gradient(to top, rgba(0,123,255,0.1) 0%, transparent 100%);
}

/* 移动端优化 */
@media (max-width: 768px) {
    .sortable-links {
        max-height: 50vh; /* 移动端稍微小一点 */
    }
}

        </style>
    </head>
    <body>
        <div class="login-box">
            <?php if(isset($error)): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            <?php if(isset($accountChanged)): ?>
                <div class="success">账号设置修改成功</div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                <div class="form-group">
                    <input type="text" name="username" placeholder="请输入账号" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="请输入密码" required>
                </div>
                <button type="submit">登录后台</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 已登录，加载数据
$currentPageId = isset($_GET['page']) ? $_GET['page'] : 'home';

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
                'url' => 'admin.php?page=' . $currentPath
            ];
        }
    }
    
    // 添加首页
    array_unshift($breadcrumbs, [
        'title' => $data['siteName'] ?? '首页',
        'url' => 'admin.php?page=home'
    ]);
    
    return $breadcrumbs;
}

$breadcrumbs = generateBreadcrumbs($currentPageId, $data);

// 获取当前页面数据
if ($currentPageId === 'home') {
    $currentPage = [
        'title' => $data['siteName'] ?? '首页',
        'links' => $data['links'] ?? []
    ];
} else {
    $currentPage = $data['pages'][$currentPageId] ?? [
        'title' => '新页面',
        'links' => []
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>后台管理 - <?= $currentPageId === 'home' ? '首页' : $currentPage['title'] ?></title>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        body { padding: 15px; background: #f0f2f5; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .form-box { background: #fff; padding: 15px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        input[type="text"], input[type="url"], input[type="password"], select, textarea { 
            width: 100%; 
            padding: 4px 6px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            margin-bottom: 4px; 
            box-sizing: border-box;
            font-family: inherit;
            font-size: 13px;
            height: 26px;
            line-height: 1.2;
        }
        textarea {
            height: auto;
            min-height: 50px;
            padding: 4px 6px;
            line-height: 1.3;
            font-size: 13px;
        }
        select {
            height: 26px;
            padding: 3px 6px;
            font-size: 12px;
        }
        button { 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 4px 10px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 12px;
            height: 24px;
            line-height: 1;
        }
        .link-inputs { 
            position: relative; 
            padding: 4px; 
            border: 1px solid #eee; 
            margin-bottom: 4px; 
            border-radius: 4px; 
            background: #fff;
        }
        .action-buttons { 
            display: flex; 
            gap: 4px; 
            margin-top: 6px; 
        }
        .action-buttons button { 
            flex: 1; 
            padding: 4px 8px; 
            height: 26px;
            font-size: 12px;
        }
        .danger { background: #dc3545; }
        .success { background: #28a745; }
        .breadcrumb { margin-bottom: 15px; }
        .breadcrumb a { color: #007bff; text-decoration: none; }
        .breadcrumb span { margin: 0 5px; color: #666; }
        .password-form { 
            margin-top: 20px; 
            padding: 15px; 
            background: #f8f9fa; 
            border-radius: 8px; 
        }
        .error { color: red; margin-bottom: 1rem; }
        .success-msg { color: green; margin-bottom: 1rem; }
        .delete-page-btn { 
            margin-top: 20px; 
            background: #dc3545; 
            color: white; 
            padding: 10px; 
            border-radius: 8px; 
            text-align: center; 
            cursor: pointer; 
        }
        .qrcode-content { 
            display: none; 
        }
        .url-display {
            background: #f8f9fa;
            padding: 6px;
            border-radius: 4px;
            margin-bottom: 6px;
            font-size: 12px;
            word-break: break-all;
            line-height: 1.3;
        }
        .visibility-toggle {
            margin-bottom: 6px;
            padding: 6px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 13px;
            line-height: 1.2;
        }
        .visibility-toggle label {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            line-height: 1.2;
        }
        
        /* 拖拽排序相关样式 - 优化移动端体验 */
        .drag-handle {
            cursor: move;
            padding: 4px 8px;
            background: #f0f0f0;
            border-radius: 4px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
            user-select: none;
            touch-action: none;
            font-size: 12px;
            line-height: 1.2;
            min-height: 24px;
            justify-content: center;
            box-sizing: border-box;
            max-width: 100%;
        }
        .drag-handle::before {
            content: "☰ 移动排序";
            font-size: 12px;
            white-space: nowrap;
        }
        .sortable-ghost {
            opacity: 0.5;
            background: #c8ebfb;
        }
        .sortable-chosen {
            opacity: 0.8;
            transform: rotate(2deg);
        }
        .sortable-drag {
            opacity: 1 !important;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .link-header {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 8px;
        }
        
        .link-title {
            font-size: 13px;
            font-weight: 500;
            color: #000000;
            padding: 2px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }
        
        .link-header-controls {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
            width: calc(100% - 8px);
            box-sizing: border-box;
            padding: 0;
            margin: 0;
        }
        
        .drag-handle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 28px;
            line-height: 28px;
            padding: 0 10px;
            width: 90px;
            box-sizing: border-box;
            font-size: 12px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 4px;
            vertical-align: middle;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .toggle-btn {
            background: none;
            color: #666;
            border: none;
            border-radius: 0;
            padding: 0 8px;
            width: auto;
            height: 28px;
            font-size: 12px;
            line-height: 28px;
            flex-shrink: 0;
            font-size: 11px;
            cursor: pointer;
            height: 26px;
            line-height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: top;
            white-space: nowrap;
            font-weight: bold;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            -webkit-user-drag: none;
            -webkit-touch-callout: none;
            -webkit-text-size-adjust: 100%;
            touch-action: manipulation;
            -webkit-user-drag: none;
            -webkit-touch-callout: none;
            -webkit-text-size-adjust: 100%;
        }
        
        .toggle-btn:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        
        .link-content {
            transition: all 0.3s ease;
        }
        
        .link-content.collapsed {
            display: none !important;
        }
        
        /* 优化删除链接仅在展开时显示 */
        .link-content.collapsed + .action-buttons {
            display: none !important;
        }
        
        /* 根据显示状态改变链接标题颜色 */
        .link-title.hidden-link {
            color: #999 !important;
        }
        
        .link-title.visible-link {
            color: #000000 !important;
        }

        /* 优化文字颜色选择框样式 - 正方形 */
        input[type="color"] {
            width: 22px !important;
            height: 22px !important;
            border-radius: 2px !important;
            padding: 0 !important;
            border: 1px solid #ddd !important;
            cursor: pointer;
        }
        
        /* 优化显示此链接和文字颜色的对齐 */
        .visibility-toggle {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            margin-bottom: 6px !important;
            flex-wrap: nowrap !important;
        }
        
        .visibility-toggle label {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            margin: 0 !important;
            font-size: 11px !important;
            color: #333 !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
            height: 22px !important;
            white-space: nowrap !important;
        }
        
        .visibility-toggle > div {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-size: 11px !important;
            color: #333 !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
            height: 22px !important;
            white-space: nowrap !important;
        }
        
        /* 移动端优化 - 确保同一行显示 */
        @media (max-width: 767px) {
            .visibility-toggle {
                gap: 8px !important;
                flex-wrap: nowrap !important;
                padding: 0 !important;
                margin: 0 !important;
                white-space: nowrap !important;
                overflow: visible !important;
            }
            
            .visibility-toggle label,
            .visibility-toggle > div {
                font-size: 11px !important;
                font-weight: 600 !important;
                height: 22px !important;
                line-height: 22px !important;
                flex-shrink: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                vertical-align: top !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* 确保标签和颜色框紧挨着显示 */
            .visibility-toggle label {
                margin-right: 4px !important;
            }
            
            .visibility-toggle > div {
                margin-left: 4px !important;
            }
            
            input[type="color"] {
                width: 20px !important;
                height: 20px !important;
                vertical-align: middle !important;
                margin: 0 !important;
            }
        }
        
        /* 根据显示状态改变链接标题颜色 */
        .link-title.hidden-link {
            color: #999 !important;
        }
        
        .link-title.visible-link {
            color: #000000 !important;
        }

        /* 优化文字颜色选择框样式 - 正方形 */
        input[type="color"] {
            width: 22px !important;
            height: 22px !important;
            border-radius: 2px !important;
            padding: 0 !important;
            border: 1px solid #ddd !important;
            cursor: pointer;
        }
        
        /* 优化显示此链接和文字颜色的对齐 */
        .visibility-toggle {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            margin-bottom: 6px !important;
            flex-wrap: nowrap !important;
        }
        
        .visibility-toggle label {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            margin: 0 !important;
            font-size: 11px !important;
            color: #333 !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
            height: 22px !important;
            white-space: nowrap !important;
        }
        
        .visibility-toggle > div {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-size: 11px !important;
            color: #333 !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
            height: 22px !important;
            white-space: nowrap !important;
        }
        
        /* 移动端优化 - 确保同一行显示 */
        @media (max-width: 767px) {
            .visibility-toggle {
                gap: 8px !important;
                flex-wrap: nowrap !important;
                padding: 0 !important;
                margin: 0 !important;
                white-space: nowrap !important;
                overflow: visible !important;
            }
            
            .visibility-toggle label,
            .visibility-toggle > div {
                font-size: 11px !important;
                font-weight: 600 !important;
                height: 22px !important;
                line-height: 22px !important;
                flex-shrink: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                vertical-align: top !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* 确保标签和颜色框紧挨着显示 */
            .visibility-toggle label {
                margin-right: 4px !important;
            }
            
            .visibility-toggle > div {
                margin-left: 4px !important;
            }
            
            input[type="color"] {
                width: 20px !important;
                height: 20px !important;
                vertical-align: middle !important;
                margin: 0 !important;
            }
        }
        
        /* 响应式设计 - 桌面端布局 */
        @media (min-width: 768px) {
            .link-header {
                flex-direction: column;
                gap: 6px;
                margin-bottom: 8px;
            }
            
        .link-title {
            font-size: 13px;
            font-weight: 500;
            color: #000000;
            padding: 2px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }
            
        .link-header-controls {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
            width: calc(100% - 8px);
            height: 32px;
            align-items: center;
            padding: 0;
            box-sizing: border-box;
            margin: 0;
        }
            
        .drag-handle {
            width: 78%;
            height: 100%;
            box-sizing: border-box;
            min-width: 0;
            max-width: none;
            line-height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 4px;
            font-weight: 600;
            margin-left: 0;
            flex-shrink: 0;
        }
            
        .toggle-btn {
            width: 22%;
            height: 100%;
            min-width: 0;
            max-width: none;
            font-size: 11px;
            padding: 0 8px;
            box-sizing: border-box;
            line-height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            background: #e9ecef;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-weight: bold;
            flex-shrink: 0;
        }
            
            /* 桌面端：样式配置在同一行 */
            .visibility-toggle[style*="display: flex"] {
                flex-direction: row;
                flex-wrap: nowrap;
            }
        }
        
        @media (max-width: 767px) {
            .form-box { padding: 10px; }
            button { font-size: 12px; }
            .action-buttons { flex-wrap: wrap; }
            .action-buttons button { 
                flex: 1 0 calc(50% - 2px); 
                margin-bottom: 4px; 
                min-width: 0;
                font-size: 11px;
                padding: 4px 6px;
            }
        .link-header-controls {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
            width: calc(100% - 8px);
            height: 32px;
            align-items: center;
            padding: 0;
            box-sizing: border-box;
            margin: 0;
        }
            
            .drag-handle { 
                padding: 0 12px; 
                font-size: 11px; 
                width: 78%;
                height: 100%;
                box-sizing: border-box;
                min-width: 0;
                max-width: none;
                line-height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                border: 1px solid #ddd;
                background: #f8f9fa;
                border-radius: 4px;
                font-weight: 600;
                flex-shrink: 0;
            }
            
            .toggle-btn {
                width: 22%;
                height: 100%;
                min-width: 0;
                max-width: none;
                font-size: 11px;
                padding: 0 8px;
                box-sizing: border-box;
                line-height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                background: #e9ecef;
                color: #666;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-weight: bold;
                flex-shrink: 0;

            
            .drag-handle::before {
                content: "☰ 移动排序";
                font-size: 11px;
                white-space: nowrap;
            }
            .link-inputs {
                padding: 4px;
            }
            .link-header {
                gap: 4px;
            }
            select {
                font-size: 12px;
                padding: 4px 6px;
            }
            
            /* 移动端：样式配置在第二行 */
            .visibility-toggle[style*="display: flex"] {
                flex-direction: column !important;
                gap: 8px !important;
            }
            
            /* 网站名称样式配置优化 */
            .form-section > div > div {
                flex-wrap: wrap;
                gap: 4px !important;
            }
            
            /* 统计显示和路径显示：移动到第二行 */
            .form-section > div[style*="flex-wrap"] > .visibility-toggle {
                flex: 1 0 100% !important;
                min-width: 100% !important;
            }
        }
        
        @media (max-width: 480px) {
            .action-buttons button { 
                flex: 1 0 100%; 
            }
            
            /* 移动端：只修改按钮占比，其他保持不变 */
            .link-header-controls {
                justify-content: space-between;
                width: 100%;
                gap: 8px;
            }
            
            .drag-handle {
                flex: 1;
                max-width: calc(50% - 4px);
                min-width: 0;
            }
            
            .toggle-btn {
                flex: 1;
                max-width: calc(50% - 4px);
                min-width: 0;
            }
        }
        
        small {
            font-size: 11px;
            line-height: 1.2;
        }
        
        .form-section {
            margin-bottom: 12px;
        }
        
        /* 样式配置区域样式 */
        .style-config-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .style-config-group h4 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 14px;
            font-weight: 600;
        }
        
        .style-inputs {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }
        
        @media (min-width: 768px) {
            .style-inputs {
                grid-template-columns: 1fr 80px 1fr;
            }
        }
        
        .style-inputs input[type="color"] {
            height: 32px;
            padding: 2px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        
        /* 优化下拉菜单选项显示 */
        select option {
            font-size: 13px;
            padding: 4px;
        }
        
        /* 修复排序提示样式 */
        .sorting-tip {
            margin-bottom: 12px;
            padding: 8px;
            background: #e9f7fe;
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>后台管理 - <?= $currentPageId === 'home' ? '首页' : $currentPage['title'] ?></h2>
        <div>
            <a href="?page=home">返回首页</a> | 
            <a href="?logout=1">退出登录</a>
        </div>
    </div>

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

    <div class="form-box">
        <form action="save.php" method="post" id="mainForm" onsubmit="return prepareForm()">
            <input type="hidden" name="page" value="<?= htmlspecialchars($currentPageId) ?>">
            
            <?php if ($currentPageId === 'home'): ?>
                <div class="form-section" style="margin-bottom: 15px;">
                    <div class="settings-header" style="display: flex; justify-content: space-between; align-items: center; background: #e9ecef; padding: 8px 12px; border-radius: 6px; cursor: pointer;" onclick="toggleSiteSettings()">
                        <h3 style="margin: 0; font-size: 14px; color: #495057; font-weight: 600;">网站设置</h3>
                        <span id="siteSettingsToggle" style="font-size: 12px; color: #666; font-weight: bold;">▲ 折叠</span>
                    </div>
                    <div id="siteSettingsContent" style="display: none; margin-top: 10px;">
                        <div class="form-section">
                            <div style="margin-bottom: 4px;">
                                <input type="text" name="siteName" placeholder="网站名称" value="<?= htmlspecialchars($data['siteName'] ?? '') ?>" required style="width: 100%;">
                            </div>
                            <div style="display: flex; gap: 6px; align-items: center; font-size: 11px; color: #666; margin-bottom: 4px; padding: 4px 8px; background: #f8f9fa; border-radius: 4px;">
                                <span style="min-width: 22px; color: #495057; font-weight: 500;">大小</span>
                                <input type="text" name="siteName_fontSize" placeholder="大小" value="<?= preg_replace('/[^-0-9.]/', '', $data['styleConfig']['siteName']['fontSize'] ?? '2.5') ?>" oninput="this.value=this.value.replace(/[^-0-9.]/g,'')" style="width: 35px; padding: 2px 4px; font-size: 10px; border: 1px solid #ddd; border-radius: 3px; text-align: center;">
                                <span style="min-width: 20px; color: #495057; font-weight: 500;">颜色</span>
                                <input type="color" name="siteName_color" value="<?= htmlspecialchars($data['styleConfig']['siteName']['color'] ?? '#1a1a1a') ?>" style="width: 20px; height: 18px; cursor: pointer; border: 1px solid #ddd; border-radius: 2px;">
                                <span style="min-width: 28px; color: #495057; font-weight: 500;">下边距</span>
                                <input type="text" name="siteName_marginBottom" placeholder="下边距" value="<?= preg_replace('/[^-0-9.]/', '', $data['styleConfig']['siteName']['marginBottom'] ?? '15') ?>" oninput="this.value=this.value.replace(/[^-0-9.]/g,'')" style="width: 35px; padding: 2px 4px; font-size: 10px; border: 1px solid #ddd; border-radius: 3px; text-align: center;">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div style="margin-bottom: 4px;">
                                <div class="visibility-toggle">
                                    <label>
                                        <input type="checkbox" name="showStats" value="1" <?= ($data['showStats'] ?? true) ? 'checked' : '' ?>>
                                        显示访问量统计（总访问量/今日访问量）
                                    </label>
                                </div>
                            </div>
                            <div style="display: flex; gap: 6px; align-items: center; font-size: 11px; color: #666; margin-bottom: 4px; padding: 4px 8px; background: #f8f9fa; border-radius: 4px;">
                                <span style="min-width: 22px; color: #495057; font-weight: 500;">大小</span>
                                <input type="text" name="stats_fontSize" placeholder="大小" value="<?= preg_replace('/[^-0-9.]/', '', $data['styleConfig']['stats']['fontSize'] ?? '14') ?>" oninput="this.value=this.value.replace(/[^-0-9.]/g,'')" style="width: 35px; padding: 2px 4px; font-size: 10px; border: 1px solid #ddd; border-radius: 3px; text-align: center;">
                                <span style="min-width: 20px; color: #495057; font-weight: 500;">颜色</span>
                                <input type="color" name="stats_color" value="<?= htmlspecialchars($data['styleConfig']['stats']['color'] ?? '#666') ?>" style="width: 20px; height: 18px; cursor: pointer; border: 1px solid #ddd; border-radius: 2px;">
                                <span style="min-width: 28px; color: #495057; font-weight: 500;">上边距</span>
                                <input type="text" name="stats_marginTop" placeholder="上边距" value="<?= preg_replace('/[^-0-9.]/', '', $data['styleConfig']['stats']['marginTop'] ?? '0') ?>" oninput="this.value=this.value.replace(/[^-0-9.]/g,'')" style="width: 35px; padding: 2px 4px; font-size: 10px; border: 1px solid #ddd; border-radius: 3px; text-align: center;">
                            </div>

                            <div style="margin-bottom: 4px;">
                                <div class="visibility-toggle">
                                    <label>
                                        <input type="checkbox" name="showBreadcrumb" value="1" <?= ($data['showBreadcrumb'] ?? true) ? 'checked' : '' ?>>
                                        显示子页面路径（点击路径/可返回上级）
                                    </label>
                                </div>
                            </div>
                            <div style="margin-bottom: 4px;">
                                <div class="visibility-toggle">
                                    <label style="display: block; margin-bottom: 6px;">电脑版每行链接数量</label>
                                    <label style="display: inline-flex; align-items: center; margin-right: 16px;">
                                        <input type="radio" name="linksPerRow" value="1" <?= ($data['linksPerRow'] ?? 2) == 1 ? 'checked' : '' ?>>
                                        <span style="margin-left: 4px; font-size: 11px;">1个</span>
                                    </label>
                                    <label style="display: inline-flex; align-items: center; margin-right: 16px;">
                                        <input type="radio" name="linksPerRow" value="2" <?= ($data['linksPerRow'] ?? 2) == 2 ? 'checked' : '' ?>>
                                        <span style="margin-left: 4px; font-size: 11px;">2个</span>
                                    </label>
                                    <label style="display: inline-flex; align-items: center;">
                                        <input type="radio" name="linksPerRow" value="3" <?= ($data['linksPerRow'] ?? 2) == 3 ? 'checked' : '' ?>>
                                        <span style="margin-left: 4px; font-size: 11px;">3个</span>
                                    </label>
                                </div>
                            </div>
                            <div style="display: flex; gap: 6px; align-items: center; font-size: 11px; color: #666; margin-bottom: 4px; padding: 4px 8px; background: #f8f9fa; border-radius: 4px;">
                                <span style="min-width: 22px; color: #495057; font-weight: 500;">大小</span>
                                <input type="text" name="breadcrumb_fontSize" placeholder="大小" value="<?= preg_replace('/[^-0-9.]/', '', $data['styleConfig']['breadcrumb']['fontSize'] ?? '16') ?>" oninput="this.value=this.value.replace(/[^-0-9.]/g,'')" style="width: 35px; padding: 2px 4px; font-size: 10px; border: 1px solid #ddd; border-radius: 3px; text-align: center;">
                                <span style="min-width: 20px; color: #495057; font-weight: 500;">颜色</span>
                                <input type="color" name="breadcrumb_color" value="<?= htmlspecialchars($data['styleConfig']['breadcrumb']['color'] ?? '#666') ?>" style="width: 20px; height: 18px; cursor: pointer; border: 1px solid #ddd; border-radius: 2px;">
                                <span style="min-width: 28px; color: #495057; font-weight: 500;">下边距</span>
                                <input type="text" name="breadcrumb_marginBottom" placeholder="下边距" value="<?= preg_replace('/[^-0-9.]/', '', $data['styleConfig']['breadcrumb']['marginBottom'] ?? '15') ?>" oninput="this.value=this.value.replace(/[^-0-9.]/g,'')" style="width: 35px; padding: 2px 4px; font-size: 10px; border: 1px solid #ddd; border-radius: 3px; text-align: center;">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div style="margin-bottom: 4px;">
                                <textarea name="footerCopyright" placeholder="底部版权信息（支持HTML代码）" rows="2" style="width: 100%;"><?= htmlspecialchars($data['footerCopyright'] ?? '© CPMYNAV智能导航建站管理系统. All rights reserved.') ?></textarea>
                            </div>
                            <div style="display: flex; gap: 6px; align-items: center; font-size: 11px; color: #666; margin-bottom: 4px; padding: 4px 8px; background: #f8f9fa; border-radius: 4px;">
                                <span style="min-width: 22px; color: #495057; font-weight: 500;">大小</span>
                                <input type="text" name="copyright_fontSize" placeholder="大小" value="<?= preg_replace('/[^-0-9.]/', '', $data['styleConfig']['copyright']['fontSize'] ?? '14') ?>" oninput="this.value=this.value.replace(/[^-0-9.]/g,'')" style="width: 35px; padding: 2px 4px; font-size: 10px; border: 1px solid #ddd; border-radius: 3px; text-align: center;">
                                <span style="min-width: 20px; color: #495057; font-weight: 500;">颜色</span>
                                <input type="color" name="copyright_color" value="<?= htmlspecialchars($data['styleConfig']['copyright']['color'] ?? '#666') ?>" style="width: 20px; height: 18px; cursor: pointer; border: 1px solid #ddd; border-radius: 2px;">
                                <span style="min-width: 28px; color: #495057; font-weight: 500;">上边距</span>
                                <input type="text" name="copyright_marginTop" placeholder="上边距" value="<?= preg_replace('/[^-0-9.]/', '', $data['styleConfig']['copyright']['marginTop'] ?? '-15') ?>" oninput="this.value=this.value.replace(/[^-0-9.]/g,'')" style="width: 35px; padding: 2px 4px; font-size: 10px; border: 1px solid #ddd; border-radius: 3px; text-align: center;">
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="form-section">
                    <input type="text" name="pageTitle" placeholder="页面标题" value="<?= htmlspecialchars($currentPage['title']) ?>" required>
                </div>
            <?php endif; ?>
            
            <div class="sorting-tip">
                <strong>💡 排序提示：</strong> 拖拽 <strong style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px; display: inline-block;">☰ 移动排序</strong> 调整链接顺序
            </div>
            
            <div id="links" class="sortable-links">
                <?php foreach($currentPage['links'] as $index => $link): ?>
                    <div class="link-inputs" data-index="<?= $index ?>" data-collapsed="false">
                        <div class="link-header">
                            <div class="link-title">链接 #<?= $index + 1 ?>: <?= htmlspecialchars($link['name']) ?></div>
                            <div class="link-header-controls">
                                <div class="drag-handle" title="拖拽移动排序"></div>
                                <button type="button" class="toggle-btn" onclick="toggleLinkContent(this)" title="折叠/展开链接设置">折叠</button>
                            </div>
                        </div>
                        
                <div class="link-content" style="display: block;">
                
                <input type="hidden" name="link_ids[]" value="<?= $index ?>">
                
                <div class="visibility-toggle">
                    <label>
                        <input type="checkbox" name="link_visible[<?= $index ?>]" value="1" <?= (!isset($link['visible']) || $link['visible']) ? 'checked' : '' ?>>
                        显示此链接
                    </label>
                    <div>
                        <span>文字颜色</span>
                        <input type="color" name="link_color[<?= $index ?>]" value="<?= htmlspecialchars($link['style']['color'] ?? '#1a1a1a') ?>">
                    </div>
                </div>
                        
                        <input type="text" name="link_names[<?= $index ?>]" placeholder="链接名称" value="<?= htmlspecialchars($link['name']) ?>" required>
                        
                        <?php if (isset($link['isQRCode']) && $link['isQRCode']): ?>
    <div class="url-display">
        <strong>二维码链接：</strong>/qrcode_display.php?content=<?= htmlspecialchars($link['qrcodeContent'] ?? '') ?>
    </div>
    <input type="hidden" name="link_urls[<?= $index ?>]" value="/qrcode_display.php?content=<?= htmlspecialchars(rawurlencode($link['qrcodeContent'] ?? '')) ?>">
<?php else: ?>
                            <input type="text" name="link_urls[<?= $index ?>]" placeholder="链接地址" value="<?= htmlspecialchars($link['url']) ?>">
                        <?php endif; ?>
                        
                        <input type="url" name="link_icons[<?= $index ?>]" placeholder="图标地址（可选）" value="<?= htmlspecialchars($link['icon'] ?? '') ?>">
                        <select name="link_types[<?= $index ?>]" onchange="toggleUrlInput(this)">
                            <option value="external" <?= (!isset($link['isPage']) || !$link['isPage']) && !isset($link['isQRCode']) ? 'selected' : '' ?>>外部链接</option>
                            <option value="page" <?= isset($link['isPage']) && $link['isPage'] ? 'selected' : '' ?>>子页面</option>
                            <option value="qrcode" <?= isset($link['isQRCode']) && $link['isQRCode'] ? 'selected' : '' ?>>二维码</option>
                        </select>
                        <div class="url-tip" style="display: <?= isset($link['isPage']) && $link['isPage'] ? 'block' : 'none' ?>; color: #666; font-size: 11px; margin-top: -2px; margin-bottom: 6px; line-height: 1.2;">
                            子页面路径应以斜杠开头，只能包含字母、数字、下划线和连字符，例如：/my-page
                        </div>
                        <div class="qrcode-content" style="display: <?= isset($link['isQRCode']) && $link['isQRCode'] ? 'block' : 'none' ?>;">
                            <textarea name="link_qrcode_contents[<?= $index ?>]" placeholder="输入二维码内容（文字、链接等）" rows="2"><?= htmlspecialchars($link['qrcodeContent'] ?? '') ?></textarea>
                        </div>
                        
                        </div>
                        
                        <div class="action-buttons" style="display: block;">
                            <button type="button" class="danger" onclick="removeLink(this)">删除链接</button>
                            <?php if (isset($link['isPage']) && $link['isPage']): ?>
                                <?php
                                $pagePath = ltrim($link['url'], '/');
                                $pageExists = isset($data['pages'][$pagePath]);
                                ?>
                                <?php if ($pageExists): ?>
                                    <a href="?page=<?= urlencode($pagePath) ?>">
                                        <button type="button" class="success">管理子页面</button>
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="success" style="background-color: #ffc107; color: #000;" 
                                            onclick="alert('页面不存在，请先保存创建页面')">页面未创建</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" onclick="addLink()" style="margin-bottom:12px; height: 32px;">+ 添加新链接</button>
            <button type="submit" style="width:100%; height: 36px;">保存所有更改</button>
        </form>
    </div>

    <?php if ($currentPageId !== 'home'): ?>
        <div class="form-box">
            <div class="delete-page-btn" onclick="if(confirm('确定要删除这个页面吗？此操作将删除页面及其所有链接，且不可恢复！')) { window.location.href='?delete_page=1&page_id=<?= urlencode($currentPageId) ?>'; }">
                删除当前页面
            </div>
        </div>
    <?php endif; ?>


    <div class="form-section" style="margin-bottom: 15px;">
        <div class="settings-header" style="display: flex; justify-content: space-between; align-items: center; background: #e9ecef; padding: 8px 12px; border-radius: 6px; cursor: pointer;" onclick="toggleAccountSettings()">
            <h3 style="margin: 0; font-size: 14px; color: #495057; font-weight: 600;">账号设置</h3>
            <span id="accountSettingsToggle" style="font-size: 12px; color: #666; font-weight: bold;">▲ 折叠</span>
        </div>
        <div id="accountSettingsContent" style="display: none; margin-top: 10px;">
            <div class="form-box password-form">
                <?php if(isset($accountError)): ?>
                    <div class="error"><?= $accountError ?></div>
                <?php endif; ?>
                <?php if(isset($accountChanged)): ?>
                    <div class="success-msg">账号设置修改成功</div>
                <?php endif; ?>
                <form method="post">
                    <input type="text" name="current_username" placeholder="当前账号" required>
                    <input type="password" name="current_password" placeholder="当前密码" required>
                    <input type="text" name="new_username" placeholder="新账号（留空则不修改）">
                    <input type="password" name="new_password" placeholder="新密码（留空则不修改）">
                    <input type="password" name="confirm_password" placeholder="确认新密码">
                    <button type="submit" name="change_account">修改账号设置</button>
                </form>
            </div>
        </div>
    </div>

<?php
// 处理授权码验证
if(isset($_POST['verify_license'])) {
    $license_code = $_POST['license_code'] ?? '';
    
    // 验证授权码规则
    $is_valid = validateLicenseCode($license_code);
    
    if($is_valid) {
        // 将授权状态保存到数据文件中
        $data['license_verified'] = true;
        file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $license_success = "授权码验证成功";
        // 重新加载数据以获取最新授权状态
        $data = json_decode(file_get_contents(DATA_FILE), true);
    } else {
        $license_error = "授权码无效，请检查格式是否正确。";
    }
}

// 授权码验证函数
function validateLicenseCode($code) {
    // 检查长度
    if(strlen($code) !== 15) {
        return false;
    }
    
    // 检查前2位
    if(substr($code, 0, 2) !== 'CP') {
        return false;
    }
    
    // 检查3-10位是否为当前日期
    $current_date = date('Ymd');
    $code_date = substr($code, 2, 8);
    if($code_date !== $current_date) {
        return false;
    }
    
    // 检查后5位：大写字母和数字，且必须包含W
    $suffix = substr($code, 10, 5);
    if(!preg_match('/^[A-Z0-9]+$/', $suffix)) {
        return false;
    }
    if(strpos($suffix, 'W') === false) {
        return false;
    }
    
    return true;
}

// 检查是否已授权
$is_licensed = $data['license_verified'] ?? false;

if(!$is_licensed): ?>
<div class="form-box password-form">
    <h3>授权码验证</h3>
    
    <?php if(isset($license_success)): ?>
        <div class="success-msg"><?= $license_success ?></div>
    <?php endif; ?>
    
    <?php if(isset($license_error)): ?>
        <div class="error"><?= $license_error ?></div>
    <?php endif; ?>
    
    <form method="post">
        <input type="text" name="license_code" placeholder="请输入15位授权码" required style="width: 100%;">
        <button type="submit" name="verify_license">验证授权码</button>
    </form>
    <div style="margin-top: 10px; font-size: 12px; color: #666;">
        <p><strong>授权码规则：</strong></p>
        <p>• 授权码限当日使用有效</p>
        <p>• 移动端请 <a href="https://qr.alipay.com/00c17351wc4gkhzk25kgu79" target="_blank" style="color: #06c; text-decoration: none;">点击此处购买</a></p>
        <p>• 软著登字第17340870号</p>
    </div>
</div>
<?php endif; ?>

<?php if($is_licensed): ?>
<div class="license-status" style="
    text-align: center;
    color: #28a745;
    font-size: 14px;
    font-weight: bold;
    padding: 8px 16px;
    margin-top: 10px;
">已授权</div>
<?php endif; ?>

    <script>
        // 生成唯一ID
        let nextLinkId = <?= count($currentPage['links']) ?>;

// 初始化拖拽排序 - 优化版（确保每个链接单独移动）
document.addEventListener('DOMContentLoaded', function() {
    const sortableContainer = document.getElementById('links');
    
    const sortable = new Sortable(sortableContainer, {
        animation: 300,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        filter: '.link-content', // 防止拖动内容区域时触发排序
        preventOnFilter: false,
        
        // 优化滚动配置
        scroll: true,
        scrollSensitivity: 25,
        scrollSpeed: 10,
        
        // 事件处理
        onStart: function(evt) {
            // 添加视觉反馈
            evt.item.style.opacity = '0.8';
            evt.item.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
            
            // 启用页面滚动
            document.body.style.overflow = 'auto';
            document.documentElement.style.overflow = 'auto';
        },
        
        onEnd: function(evt) {
            // 恢复样式
            evt.item.style.opacity = '';
            evt.item.style.boxShadow = '';
            
            // 更新链接编号
            updateLinkNumbers();
            
            // 恢复滚动设置
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        },
        
        onUpdate: function(evt) {
            // 拖拽完成后的回调
            console.log('链接顺序已更新');
        }
    });
    
    // 初始更新链接编号
    updateLinkNumbers();
});

// 自定义边缘滚动函数
function enableEdgeScrolling(container) {
    let scrollInterval;
    
    container.addEventListener('mousemove', function(e) {
        if (scrollInterval) {
            clearInterval(scrollInterval);
        }
        
        const rect = container.getBoundingClientRect();
        const edgeThreshold = 40; // 距离边缘40像素开始滚动
        
        const isNearTop = e.clientY < rect.top + edgeThreshold;
        const isNearBottom = e.clientY > rect.bottom - edgeThreshold;
        
        if (isNearTop || isNearBottom) {
            const scrollDirection = isNearTop ? -1 : 1;
            const scrollAmount = 8; // 每次滚动的像素数
            
            scrollInterval = setInterval(function() {
                container.scrollTop += scrollDirection * scrollAmount;
            }, 16); // 约60fps
        }
    });
    
    container.addEventListener('mouseleave', function() {
        if (scrollInterval) {
            clearInterval(scrollInterval);
            scrollInterval = null;
        }
    });
}

        function updateLinkNumbers() {
            const links = document.querySelectorAll('.link-inputs');
            links.forEach((link, index) => {
                const title = link.querySelector('.link-title');
                if (title) {
                    title.textContent = `链接 #${index + 1}: ${link.querySelector('input[name^="link_names"]').value}`;
                }
                link.setAttribute('data-index', index);
            });
        }

function addLink() {
    const div = document.createElement('div');
    div.className = 'link-inputs';
    const newIndex = nextLinkId++;
    div.setAttribute('data-index', newIndex);
    div.setAttribute('data-collapsed', 'false'); // 新链接默认展开
    div.innerHTML = `
                <div class="link-header">
                    <div class="link-title">链接 #${document.querySelectorAll('.link-inputs').length + 1}: 新链接</div>
                    <div class="link-header-controls">
                        <div class="drag-handle" title="拖拽移动排序"></div>
                        <button type="button" class="toggle-btn" onclick="toggleLinkContent(this)" title="折叠/展开链接设置">折叠</button>
                    </div>
                </div>
                
                <div class="link-content" style="display: block;">
                
                <input type="hidden" name="link_ids[]" value="${newIndex}">
                <div class="visibility-toggle">
                    <label>
                        <input type="checkbox" name="link_visible[${newIndex}]" value="1" checked>
                        显示此链接
                    </label>
                    <div>
                        <span>文字颜色</span>
                        <input type="color" name="link_color[${newIndex}]" value="#1a1a1a">
                    </div>
                </div>
                
                <input type="text" name="link_names[${newIndex}]" placeholder="链接名称" required>
                <input type="text" name="link_urls[${newIndex}]" placeholder="链接地址" required>
                <input type="url" name="link_icons[${newIndex}]" placeholder="图标地址（可选）">
                <select name="link_types[${newIndex}]" onchange="toggleUrlInput(this)">
                    <option value="external" selected>外部链接</option>
                    <option value="page">子页面</option>
                    <option value="qrcode">二维码</option>
                </select>
                <div class="url-tip" style="display: none; color: #666; font-size: 11px; margin-top: -2px; margin-bottom: 6px; line-height: 1.2;">
                    子页面路径应以斜杠开头，只能包含字母、数字、下划线和连字符，例如：/my-page
                </div>
                <div class="qrcode-content" style="display: none;">
                    <textarea name="link_qrcode_contents[${newIndex}]" placeholder="输入二维码内容（文字、链接等）" rows="2"></textarea>
                </div>
                

                
                <div class="action-buttons">
                    <button type="button" class="danger" onclick="removeLink(this)">删除链接</button>
                </div>
            `;
            document.getElementById('links').appendChild(div);
            addSortListeners(div);
            updateLinkNumbers();
            
            // 为新链接添加名称变化监听
            const nameInput = div.querySelector('input[name^="link_names"]');
            nameInput.addEventListener('input', function() {
                const title = div.querySelector('.link-title');
                if (title) {
                    const index = Array.from(document.querySelectorAll('.link-inputs')).indexOf(div) + 1;
                    title.textContent = `链接 #${index}: ${this.value}`;
                }
            });
        }

        function toggleUrlInput(select) {
            const urlInput = select.parentElement.querySelector('input[name^="link_urls"]');
            const urlTip = select.parentElement.querySelector('.url-tip');
            const qrcodeContent = select.parentElement.querySelector('.qrcode-content');
            const qrcodeTextarea = select.parentElement.querySelector('textarea[name^="link_qrcode_contents"]');
            
            // 隐藏所有提示和额外字段
            urlTip.style.display = 'none';
            qrcodeContent.style.display = 'none';
            urlInput.style.display = 'block';
            urlInput.required = true;
            
            if (select.value === 'page') {
                urlInput.type = 'text';
                urlInput.placeholder = "输入子页面路径（如：/my-page）";
                urlTip.style.display = 'block';
                
                // 如果URL为空或不是子页面路径，提供默认值
                if (!urlInput.value || !urlInput.value.startsWith('/')) {
                    const nameInput = select.parentElement.querySelector('input[name^="link_names"]');
                    let nameValue = nameInput.value.trim();
                    
                    if (nameValue) {
                        const pinyin = nameValue.replace(/[^\u4e00-\u9fa5a-zA-Z0-9]/g, '')
                                               .replace(/[\u4e00-\u9fa5]/g, '')
                                               .toLowerCase();
                        urlInput.value = '/<?= $currentPageId === 'home' ? '' : $currentPageId . '/' ?>' + 
                                        (pinyin || 'page');
                    } else {
                        urlInput.value = '/<?= $currentPageId === 'home' ? '' : $currentPageId . '/' ?>page';
                    }
                }
    } else if (select.value === 'qrcode') {
        urlInput.type = 'text';
        urlInput.placeholder = "二维码链接将自动生成";
        
        if (qrcodeTextarea && qrcodeTextarea.value) {
            const content = qrcodeTextarea.value;
            urlInput.value = "/qrcode_display.php?content=" + content;
        } else {
            urlInput.value = "/qrcode_display.php?content=";
        }
        
        urlInput.required = false;
        qrcodeContent.style.display = 'block';
        
        // 如果二维码内容为空，提供默认提示
        if (!qrcodeTextarea.value) {
            qrcodeTextarea.value = "请输入二维码内容";
        }
    } else {
                urlInput.type = 'url';
                urlInput.placeholder = "链接地址";
                
                // 如果URL是子页面路径或二维码链接，清空它
                if (urlInput.value.startsWith('/') || urlInput.value.includes('qrcode.php')) {
                    urlInput.value = '';
                }
            }
        }

        // 为二维码内容添加实时更新功能
        function setupQRCodeContentListener(container) {
            const textarea = container.querySelector('textarea[name^="link_qrcode_contents"]');
            const urlInput = container.querySelector('input[name^="link_urls"]');
            const typeSelect = container.querySelector('select[name^="link_types"]');
            
            if (textarea) {
                textarea.addEventListener('input', function() {
                    if (typeSelect.value === 'qrcode') {
                        const content = textarea.value;
                        // 显示原始内容而不是编码后的URL
                        urlInput.value = "qrcode.php?content=" + content;
                    }
                });
            }
        }

        function removeLink(btn) {
            if (confirm('确定要删除这个链接吗？')) {
                btn.closest('.link-inputs').remove();
                updateLinkNumbers();
            }
        }

        function addSortListeners(container) {
            setupQRCodeContentListener(container);
            
            // 为名称输入框添加变化监听
            const nameInput = container.querySelector('input[name^="link_names"]');
            nameInput.addEventListener('input', function() {
                const title = container.querySelector('.link-title');
                if (title) {
                    const index = Array.from(document.querySelectorAll('.link-inputs')).indexOf(container) + 1;
                    title.textContent = `链接 #${index}: ${this.value}`;
                }
            });
        }

        // 表单提交前准备
        function prepareForm() {
            // 重新索引所有链接
            const links = document.querySelectorAll('.link-inputs');
            links.forEach((link, newIndex) => {
                // 更新显示编号
                const title = link.querySelector('.link-title');
                if (title) {
                    const name = link.querySelector('input[name^="link_names"]').value;
                    title.textContent = `链接 #${newIndex + 1}: ${name}`;
                }
                
                // 更新隐藏的link_ids
                const hiddenId = link.querySelector('input[name="link_ids[]"]');
                if (hiddenId) {
                    // 这里不需要更新值，因为我们已经使用唯一ID
                }
            });
            
            return confirm('确认保存吗？');
        }

        // 链接折叠功能（简化版）
        function toggleLinkContent(button) {
            const linkContainer = button.closest('.link-inputs');
            const content = linkContainer.querySelector('.link-content');
            const actionButtons = linkContainer.querySelector('.action-buttons');
            
            // 判断当前是否折叠
            const isCollapsed = content.classList.contains('collapsed');
            
            if (isCollapsed) {
                // 当前是折叠状态，点击后展开
                content.classList.remove('collapsed');
                if (actionButtons) {
                    actionButtons.style.display = 'block';
                }
                button.textContent = '折叠';
                linkContainer.setAttribute('data-collapsed', 'false');
            } else {
                // 当前是展开状态，点击后折叠
                content.classList.add('collapsed');
                if (actionButtons) {
                    actionButtons.style.display = 'none';
                }
                button.textContent = '展开';
                linkContainer.setAttribute('data-collapsed', 'true');
            }
        }
        
        // 页面加载时初始化链接状态（保存后自动折叠）
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('.link-inputs');
            
            links.forEach((link) => {
                const content = link.querySelector('.link-content');
                const actionButtons = link.querySelector('.action-buttons');
                const toggleBtn = link.querySelector('.toggle-btn');
                
                // 强制所有链接默认折叠
                const isCollapsed = true;
                
                if (isCollapsed) {
                    content.classList.add('collapsed');
                    if (actionButtons) {
                        actionButtons.style.display = 'none';
                    }
                    if (toggleBtn) {
                        toggleBtn.textContent = '展开';
                    }
                } else {
                    content.classList.remove('collapsed');
                    if (actionButtons) {
                        actionButtons.display = 'block';
                    }
                    if (toggleBtn) {
                        toggleBtn.textContent = '折叠';
                    }
                }
                
                // 确保data属性也设置为折叠
                link.setAttribute('data-collapsed', 'true');
            });
            
            // 网站设置默认折叠
            const siteSettingsContent = document.getElementById('siteSettingsContent');
            const siteSettingsToggle = document.getElementById('siteSettingsToggle');
            if (siteSettingsContent && siteSettingsToggle) {
                siteSettingsContent.style.display = 'none';
                siteSettingsToggle.textContent = '▼ 展开';
            }
            
            // 账号设置默认折叠
            const accountSettingsContent = document.getElementById('accountSettingsContent');
            const accountSettingsToggle = document.getElementById('accountSettingsToggle');
            if (accountSettingsContent && accountSettingsToggle) {
                accountSettingsContent.style.display = 'none';
                accountSettingsToggle.textContent = '▼ 展开';
            }
            
            // 初始化链接显示状态的颜色变化
            updateLinkVisibilityColors();
            
            // 添加显示此链接复选框的事件监听
            document.querySelectorAll('input[name^="link_visible"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateLinkVisibilityColors);
            });
        });
        
        // 根据显示此链接状态更新链接标题颜色
        function updateLinkVisibilityColors() {
            document.querySelectorAll('.link-inputs').forEach(linkContainer => {
                const title = linkContainer.querySelector('.link-title');
                const checkbox = linkContainer.querySelector('input[name^="link_visible"]');
                
                if (title && checkbox) {
                    if (checkbox.checked) {
                        title.classList.remove('hidden-link');
                        title.classList.add('visible-link');
                    } else {
                        title.classList.remove('visible-link');
                        title.classList.add('hidden-link');
                    }
                }
            });
        }

        // 样式配置折叠功能
        function toggleStyleConfig() {
            const content = document.getElementById('styleConfigContent');
            const toggle = document.getElementById('styleConfigToggle');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggle.textContent = '▲';
            } else {
                content.style.display = 'none';
                toggle.textContent = '▼';
            }
        }

        // 网站设置折叠功能
        function toggleSiteSettings() {
            const content = document.getElementById('siteSettingsContent');
            const toggle = document.getElementById('siteSettingsToggle');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggle.textContent = '▲ 折叠';
            } else {
                content.style.display = 'none';
                toggle.textContent = '▼ 展开';
            }
        }

        // 账号设置折叠功能
        function toggleAccountSettings() {
            const content = document.getElementById('accountSettingsContent');
            const toggle = document.getElementById('accountSettingsToggle');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggle.textContent = '▲ 折叠';
            } else {
                content.style.display = 'none';
                toggle.textContent = '▼ 展开';
            }
        }

        // 初始化事件监听
        document.querySelectorAll('.link-inputs').forEach(addSortListeners);
        document.querySelectorAll('select[name^="link_types"]').forEach(select => {
            toggleUrlInput(select);
        });
    </script>
</body>
</html>