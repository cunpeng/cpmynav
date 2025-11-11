<?php
// config.php - 统一配置文件

// 设置数据目录路径（相对于网站根目录）
define('DATA_DIR', __DIR__ . '/data');
define('DATA_FILE', DATA_DIR . '/data.json');
define('STATS_FILE', DATA_DIR . '/stats.json');
define('BACKUP_DIR', DATA_DIR . '/backups/');

// 确保数据目录存在且有正确权限
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
    // 创建备份目录
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
}

// 如果数据文件不存在，创建默认数据
if (!file_exists(DATA_FILE)) {
    $defaultData = [
        'siteName' => '我的导航站',
        'adminPassword' => '12345678',
        'links' => [],
        'pages' => [],
        'footerCopyright' => '© 我的导航站. All rights reserved.',
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

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 错误报告设置（生产环境可关闭）
error_reporting(E_ALL);
ini_set('display_errors', 0); // 生产环境设置为0

// 安全设置
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
?>