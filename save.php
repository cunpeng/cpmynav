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

// 创建备份文件
$backupFile = BACKUP_DIR . 'data_' . date('Ymd_His') . '.json';
if(!is_dir(BACKUP_DIR)) mkdir(BACKUP_DIR, 0755, true);
copy(DATA_FILE, $backupFile);

// 读取现有数据
$data = json_decode(file_get_contents(DATA_FILE), true);

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
        
        // 保存版权信息
        $data['footerCopyright'] = $_POST['footerCopyright'] ?? '';
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
            
            // 如果是二维码类型，保存二维码内容
            if ($isQRCode) {
                if (empty($qrcodeContent)) {
                    throw new Exception('二维码内容不能为空');
                }
                $linkData['qrcodeContent'] = $qrcodeContent;
                $linkData['url'] = "qrcode_display.php?content=" . rawurlencode($qrcodeContent);
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
    
    // 写入前检查JSON有效性
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    
    // 原子写入操作
    $tmpFile = tempnam(sys_get_temp_dir(), 'data_');
    file_put_contents($tmpFile, $json);
    rename($tmpFile, DATA_FILE);

    header('Location: admin.php?page=' . urlencode($pageId));
    exit;
} catch (Exception $e) {
    // 恢复备份
    copy($backupFile, DATA_FILE);
    die('保存失败: ' . $e->getMessage());
}