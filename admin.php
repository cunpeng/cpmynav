<?php
// 引入配置文件
require_once 'config.php';

session_start();
$data = json_decode(file_get_contents(DATA_FILE), true);

// 从数据文件中获取密码，如果没有则使用默认密码
$password = $data['adminPassword'] ?? '12345678';

// 处理密码修改
if(isset($_POST['change_password'])) {
    if($_POST['current_password'] === $password) {
        if($_POST['new_password'] === $_POST['confirm_password']) {
            // 更新密码
            $data['adminPassword'] = $_POST['new_password'];
            file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $passwordChanged = true;
            $password = $_POST['new_password']; // 更新当前会话的密码
        } else {
            $passwordError = "新密码不匹配";
        }
    } else {
        $passwordError = "当前密码错误";
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
    if(isset($_POST['password'])) {
        if($_POST['password'] === $password) {
            $_SESSION['loggedin'] = true;
            // 重定向到请求的页面或首页
            $page = isset($_GET['page']) ? '?page=' . urlencode($_GET['page']) : '';
            header('Location: admin.php' . $page);
            exit();
        } else {
            $error = '密码错误';
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
        </style>
    </head>
    <body>
        <div class="login-box">
            <?php if(isset($error)): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>
            <?php if(isset($passwordChanged)): ?>
                <div class="success">密码修改成功</div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                <div class="form-group">
                    <input type="password" name="password" placeholder="请输入管理密码" required>
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
    <!-- 引入SortableJS库 -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        body { padding: 15px; background: #f0f2f5; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .form-box { background: #fff; padding: 15px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        input[type="text"], input[type="url"], input[type="password"], select, textarea { 
            width: 100%; 
            padding: 6px 8px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            margin-bottom: 6px; 
            box-sizing: border-box;
            font-family: inherit;
            font-size: 14px;
            height: 28px;
            line-height: 1.2;
        }
        textarea {
            height: auto;
            min-height: 60px;
            padding: 6px 8px;
            line-height: 1.3;
        }
        select {
            height: 28px;
            padding: 4px 8px;
            font-size: 13px;
        }
        button { 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 6px 12px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 13px;
            height: 28px;
            line-height: 1;
        }
        .link-inputs { 
            position: relative; 
            padding: 8px; 
            border: 1px solid #eee; 
            margin-bottom: 8px; 
            border-radius: 6px; 
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
            margin-bottom: 6px;
            gap: 4px;
        }
        .link-title {
            font-weight: bold;
            color: #333;
            font-size: 13px;
            line-height: 1.2;
        }
        
        /* 响应式设计 */
        @media (min-width: 768px) {
            .link-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            .drag-handle {
                width: auto;
                min-width: 100px;
                max-width: 120px;
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
            .drag-handle { 
                padding: 6px 8px; 
                font-size: 11px; 
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            .drag-handle::before {
                content: "☰ 移动排序";
                font-size: 11px;
                white-space: nowrap;
            }
            .link-inputs {
                padding: 6px;
            }
            .link-header {
                gap: 6px;
            }
            select {
                font-size: 12px;
                padding: 4px 6px;
            }
        }
        
        @media (max-width: 480px) {
            .action-buttons button { 
                flex: 1 0 100%; 
            }
        }
        
        small {
            font-size: 11px;
            line-height: 1.2;
        }
        
        .form-section {
            margin-bottom: 12px;
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

    <!-- 面包屑导航 -->
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
                <div class="form-section">
                    <input type="text" name="siteName" placeholder="网站名称" value="<?= htmlspecialchars($data['siteName'] ?? '') ?>" required>
                </div>
                
                <!-- 统计显示开关 -->
                <div class="visibility-toggle">
                    <label>
                        <input type="checkbox" name="showStats" value="1" <?= ($data['showStats'] ?? true) ? 'checked' : '' ?>>
                        显示访问量统计（总访问量/今日访问量）
                    </label>
                </div>
                
                <!-- 版权信息输入框 -->
                <div class="form-section">
                    <textarea name="footerCopyright" placeholder="底部版权信息（支持HTML代码）" rows="2"><?= htmlspecialchars($data['footerCopyright'] ?? '© 我的导航站. All rights reserved.') ?></textarea>
                </div>
                
            <?php else: ?>
                <div class="form-section">
                    <input type="text" name="pageTitle" placeholder="页面标题" value="<?= htmlspecialchars($currentPage['title']) ?>" required>
                </div>
            <?php endif; ?>
            
            <!-- 修复后的排序提示 -->
            <div class="sorting-tip">
                <strong>💡 排序提示：</strong> 拖拽 <strong style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px; display: inline-block;">☰ 移动排序</strong> 调整链接顺序
            </div>
            
            <div id="links" class="sortable-links">
                <?php foreach($currentPage['links'] as $index => $link): ?>
                    <div class="link-inputs" data-index="<?= $index ?>">
                        <div class="link-header">
                            <div class="link-title">链接 #<?= $index + 1 ?>: <?= htmlspecialchars($link['name']) ?></div>
                            <div class="drag-handle" title="拖拽移动排序"></div>
                        </div>
                        
                        <!-- 修复：使用唯一ID标识链接状态 -->
                        <input type="hidden" name="link_ids[]" value="<?= $index ?>">
                        <div class="visibility-toggle">
                            <label>
                                <input type="checkbox" name="link_visible[<?= $index ?>]" value="1" <?= (!isset($link['visible']) || $link['visible']) ? 'checked' : '' ?>>
                                显示此链接
                            </label>
                        </div>
                        
                        <input type="text" name="link_names[<?= $index ?>]" placeholder="链接名称" value="<?= htmlspecialchars($link['name']) ?>" required>
                        
                        <!-- 修复：显示原始URL而不是编码后的 -->
                        <?php if (isset($link['isQRCode']) && $link['isQRCode']): ?>
                            <div class="url-display">
                                <strong>二维码链接：</strong>qrcode.php?content=<?= htmlspecialchars($link['qrcodeContent'] ?? '') ?>
                            </div>
                            <input type="hidden" name="link_urls[<?= $index ?>]" value="qrcode.php?content=<?= htmlspecialchars(rawurlencode($link['qrcodeContent'] ?? '')) ?>">
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
                        <div class="action-buttons">
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

    <!-- 删除当前页面按钮（非首页） -->
    <?php if ($currentPageId !== 'home'): ?>
        <div class="form-box">
            <div class="delete-page-btn" onclick="if(confirm('确定要删除这个页面吗？此操作将删除页面及其所有链接，且不可恢复！')) { window.location.href='?delete_page=1&page_id=<?= urlencode($currentPageId) ?>'; }">
                删除当前页面
            </div>
        </div>
    <?php endif; ?>

    <!-- 修改密码表单 -->
    <div class="form-box password-form">
        <h3>修改管理密码</h3>
        <?php if(isset($passwordError)): ?>
            <div class="error"><?= $passwordError ?></div>
        <?php endif; ?>
        <?php if(isset($passwordChanged)): ?>
            <div class="success-msg">密码修改成功</div>
        <?php endif; ?>
        <form method="post">
            <input type="password" name="current_password" placeholder="当前密码" required>
            <input type="password" name="new_password" placeholder="新密码" required>
            <input type="password" name="confirm_password" placeholder="确认新密码" required>
            <button type="submit" name="change_password">修改密码</button>
        </form>
    </div>
    
<!-- 授权码验证表单 -->
<div class="form-box password-form">
    <h3>授权码验证</h3>
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
    ?>
    
    <?php if(isset($license_success)): ?>
        <div class="success-msg"><?= $license_success ?></div>
    <?php endif; ?>
    
    <?php if(isset($license_error)): ?>
        <div class="error"><?= $license_error ?></div>
    <?php endif; ?>
    
    <?php if(!$is_licensed): ?>
        <form method="post">
            <input type="text" name="license_code" placeholder="请输入15位授权码" required style="width: 100%;">
            <button type="submit" name="verify_license">验证授权码</button>
        </form>
        <div style="margin-top: 10px; font-size: 12px; color: #666;">
            <p><strong>授权码规则：</strong></p>
            <p>• 授权码仅限当日使用有效</p>
            <p>• 邮箱peeps@foxmail.com</p>
            <p>• 交流售后QQ群333628217</p>
        </div>
    <?php else: ?>
        <div class="success-msg">
            ✅ 已授权
        </div>
    <?php endif; ?>
</div>

    <script>
        // 生成唯一ID
        let nextLinkId = <?= count($currentPage['links']) ?>;

        // 初始化拖拽排序
        document.addEventListener('DOMContentLoaded', function() {
            const sortable = new Sortable(document.getElementById('links'), {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    updateLinkNumbers();
                }
            });
            
            // 初始更新链接编号
            updateLinkNumbers();
        });

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
            div.innerHTML = `
                <div class="link-header">
                    <div class="link-title">链接 #${document.querySelectorAll('.link-inputs').length + 1}: 新链接</div>
                    <div class="drag-handle" title="拖拽移动排序"></div>
                </div>
                
                <!-- 修复：使用唯一ID标识链接状态 -->
                <input type="hidden" name="link_ids[]" value="${newIndex}">
                <div class="visibility-toggle">
                    <label>
                        <input type="checkbox" name="link_visible[${newIndex}]" value="1" checked>
                        显示此链接
                    </label>
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
                
                // 修复：显示原始内容而不是编码后的URL
                if (qrcodeTextarea && qrcodeTextarea.value) {
                    const content = qrcodeTextarea.value;
                    // 只对URL进行编码，但显示原始内容
                    urlInput.value = "qrcode.php?content=" + content;
                } else {
                    urlInput.value = "qrcode.php?content=";
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

        // 初始化事件监听
        document.querySelectorAll('.link-inputs').forEach(addSortListeners);
        document.querySelectorAll('select[name^="link_types"]').forEach(select => {
            toggleUrlInput(select);
        });
    </script>
</body>
</html>