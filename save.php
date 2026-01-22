<?php
// 引入配置文件
require_once 'config.php';

session_start();
if(!isset($_SESSION['loggedin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('无权访问');
}

// 数据验证
if(empty($_POST['page'])) {
    die('无效的提交数据：页面ID为空');
}

// 健康检查
if (!check_health()) {
    header('HTTP/1.1 500 Internal Server Error');
    die('系统数据异常，请检查数据文件');
}

// 创建备份文件
$backupFile = BACKUP_DIR . 'data_' . date('Ymd_His') . '.json';
if(!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

// 读取现有数据
$dataContent = file_get_contents(DATA_FILE);
if ($dataContent === false) {
    die('无法读取数据文件');
}

$data = json_decode($dataContent, true);
if ($data === null) {
    die('数据文件格式错误');
}

// 构建页面数据
$pageId = $_POST['page'];

try {
    // 更新网站名称或页面标题
    if($pageId === 'home') {
        if(empty($_POST['siteName'])) {
            throw new Exception('网站名称不能为空');
        }
        $data['siteName'] = htmlspecialchars($_POST['siteName']);
        
        // 保存统计显示设置
        $data['showStats'] = isset($_POST['showStats']);
                
            // 新增：保存面包屑导航显示设置
    $data['showBreadcrumb'] = isset($_POST['showBreadcrumb']);
        
        // 保存版权信息
        $data['footerCopyright'] = $_POST['footerCopyright'] ?? '';
        
        // 保存样式配置
        if (isset($_POST['siteName_fontSize'])) {
            $data['styleConfig'] = [
                'siteName' => [
                    'fontSize' => $_POST['siteName_fontSize'] . 'rem',
                    'color' => $_POST['siteName_color'] ?? '#1a1a1a',
                    'marginBottom' => $_POST['siteName_marginBottom'] . 'px'
                ],
                'breadcrumb' => [
                    'fontSize' => $_POST['breadcrumb_fontSize'] . 'px',
                    'color' => $_POST['breadcrumb_color'] ?? '#666',
                    'marginBottom' => $_POST['breadcrumb_marginBottom'] . 'px'
                ],
                'stats' => [
                    'fontSize' => $_POST['stats_fontSize'] . 'px',
                    'color' => $_POST['stats_color'] ?? '#666',
                    'marginTop' => $_POST['stats_marginTop'] . 'px'
                ],
                'copyright' => [
                    'fontSize' => $_POST['copyright_fontSize'] . 'px',
                    'color' => $_POST['copyright_color'] ?? '#666',
                    'marginTop' => $_POST['copyright_marginTop'] . 'px'
                ]
            ];
        }
    } else {
        if(empty($_POST['pageTitle'])) {
            throw new Exception('页面标题不能为空');
        }
        $data['pages'][$pageId]['title'] = htmlspecialchars($_POST['pageTitle']);
    }
    
    // 处理链接 - 使用新的基于ID的保存逻辑
    $links = [];
    
    // 获取链接ID列表
    $linkIds = $_POST['link_ids'] ?? [];
    
    if (!empty($linkIds)) {
        foreach ($linkIds as $linkId) {
            $name = $_POST['link_names'][$linkId] ?? '';
            $url = $_POST['link_urls'][$linkId] ?? '';
            $icon = $_POST['link_icons'][$linkId] ?? '';
            $type = $_POST['link_types'][$linkId] ?? 'external';
            $qrcodeContent = $_POST['link_qrcode_contents'][$linkId] ?? '';
            $visible = isset($_POST['link_visible'][$linkId]) && $_POST['link_visible'][$linkId] == '1';
            
            if (empty($name)) {
                throw new Exception('链接名称不能为空');
            }
            
            $isPage = $type === 'page';
            $isQRCode = $type === 'qrcode';
            
            if (!$isQRCode && empty($url)) {
                throw new Exception('链接地址不能为空');
            }
            
            $url = str_replace('&amp;', '&', $url);
            
            // 处理页面类型链接的路径验证
            if ($isPage) {
                if (strpos($url, '/') !== 0) {
                    $url = '/' . $url;
                }
                
                $url = preg_replace('#/+#', '/', $url);
                
                if (!preg_match('/^\/([a-zA-Z0-9_\-\p{Han}]*\/)*[a-zA-Z0-9_\-\p{Han}]*$/u', $url)) {
                    throw new Exception('子页面路径格式不正确，只能包含字母、数字、下划线、连字符和中文');
                }
            }
            
            // 构建链接数据
            $linkData = [
                'name' => htmlspecialchars($name),
                'url' => $url,
                'icon' => $icon,
                'isPage' => $isPage,
                'isQRCode' => $isQRCode,
                'visible' => $visible
            ];
            
            // 保存链接样式配置（只保留文字颜色）
            $linkData['style'] = [
                'color' => $_POST['link_color'][$linkId] ?? '#1a1a1a'
            ];
            
// 如果是二维码类型，保存二维码内容
if ($isQRCode) {
    if (empty($qrcodeContent)) {
        throw new Exception('二维码内容不能为空');
    }
    $linkData['qrcodeContent'] = $qrcodeContent;
    // 使用绝对路径
    $linkData['url'] = "/qrcode_display.php?content=" . rawurlencode($qrcodeContent);
}
            
            $links[] = $linkData;
            
            // 如果是页面类型，确保页面存在
            if ($isPage) {
                $subPageId = ltrim($url, '/');
                if (!isset($data['pages'][$subPageId])) {
                    $data['pages'][$subPageId] = [
                        'title' => htmlspecialchars($name),
                        'links' => []
                    ];
                }
            }
        }
    }
    
    // 更新页面链接
    if ($pageId === 'home') {
        $data['links'] = $links;
    } else {
        $data['pages'][$pageId]['links'] = $links;
    }
    
    // 先备份当前文件
    copy(DATA_FILE, $backupFile);
    
    // 写入前检查JSON有效性
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new Exception('数据编码失败');
    }
    
    // 原子写入操作
    $tmpFile = tempnam(sys_get_temp_dir(), 'data_');
    file_put_contents($tmpFile, $json);
    
    if (!rename($tmpFile, DATA_FILE)) {
        // 如果重命名失败，尝试直接写入
        file_put_contents(DATA_FILE, $json);
    }

    header('Location: admin.php?page=' . urlencode($pageId));
    exit;
} catch (Exception $e) {
    // 恢复备份
    if (file_exists($backupFile)) {
        copy($backupFile, DATA_FILE);
    }
    die('保存失败: ' . $e->getMessage());
}