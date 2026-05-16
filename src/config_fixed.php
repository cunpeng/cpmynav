<?php
// config.php - 兼容宝塔面板的简化版本

// 直接使用宝塔面板环境路径，避免open_basedir限制
define('DATA_DIR', dirname(__FILE__) . '/data');
define('DATA_FILE', DATA_DIR . '/data.json');
define('STATS_FILE', DATA_DIR . '/stats.json');
define('BACKUP_DIR', DATA_DIR . '/backups/');

// 确保目录存在
if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0755, true);
}

if (!is_dir(BACKUP_DIR)) {
    @mkdir(BACKUP_DIR, 0755, true);
}

// 设置目录权限
if (is_dir(DATA_DIR)) {
    @chmod(DATA_DIR, 0755);
}
if (is_dir(BACKUP_DIR)) {
    @chmod(BACKUP_DIR, 0755);
}

// 健康检查函数
function check_health() {
    // 检查数据文件是否存在且可读
    if (!file_exists(DATA_FILE) || !is_readable(DATA_FILE)) {
        return false;
    }
    
    // 检查数据文件内容是否有效
    $data = @file_get_contents(DATA_FILE);
    if ($data === false) {
        return false;
    }
    
    $decoded = @json_decode($data, true);
    return $decoded !== null;
}

// 如果数据文件不存在，创建默认数据
if (!file_exists(DATA_FILE)) {
    $defaultData = [
        'siteName' => 'CPMYNAV智能导航建站管理系统',
        'adminPassword' => '12345678',
        'links' => [],
        'pages' => [],
        'footerCopyright' => '© CPMYNAV智能导航建站管理系统. All rights reserved.',
        'showStats' => true, // 默认显示统计信息
        'showStatsInAdmin' => true,
        'license_verified' => false,  // 新增授权状态字段
        'showBreadcrumb' => true  // 新增：默认显示面包屑导航
    ];
    file_put_contents(DATA_FILE, json_encode($defaultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    chmod(DATA_FILE, 0644);
}

// 如果统计文件不存在，创建默认统计
if (!file_exists(STATS_FILE)) {
    $defaultStats = [
        'total' => 0,
        'daily' => [date('Ymd') => 0],
        'last_ip' => '',
        'ips' => []
    ];
    file_put_contents(STATS_FILE, json_encode($defaultStats, JSON_PRETTY_PRINT));
    chmod(STATS_FILE, 0644);
}
?>