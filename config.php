<?php
// config.php - 兼容Docker和宝塔面板的配置文件

// 检测运行环境，自动适配路径
// 使用更安全的方法避免open_basedir限制
$is_docker = false;

// 更可靠的Docker环境检测方法
if (@file_exists('/.dockerenv')) {
    // 如果存在.dockerenv文件，说明是Docker环境
    $is_docker = true;
} elseif (function_exists('posix_getuid') && posix_getuid() == 0) {
    // 如果是root用户，可能是Docker环境
    $is_docker = true;
}

// 设置数据目录
if ($is_docker) {
    // Docker环境 - 使用容器内的/data目录
    define('DATA_DIR', '/data');
} else {
    // 宝塔面板环境 - 使用网站根目录下的data文件夹
    define('DATA_DIR', dirname(__FILE__) . '/data');
}

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
        'adminUsername' => 'admin',
        'adminPassword' => '12345678',
        'links' => [],
        'pages' => [],
        'footerCopyright' => '© CPMYNAV智能导航建站管理系统. All rights reserved.',
        'showStats' => true, // 默认显示统计信息
        'showStatsInAdmin' => true,
        'license_verified' => false,  // 新增授权状态字段
        'showBreadcrumb' => true,  // 新增：默认显示面包屑导航
        'linksPerRow' => 2,  // 每行显示链接数量，默认2
        // 样式配置
        'styleConfig' => [
            'siteName' => [
                'fontSize' => '2.5rem',
                'color' => '#1a1a1a',
                'marginBottom' => '15px'
            ],
            'breadcrumb' => [
                'fontSize' => '16px',
                'color' => '#666',
                'marginBottom' => '15px'
            ],
            'stats' => [
                'fontSize' => '14px',
                'color' => '#666',
                'marginTop' => '0px'
            ],
            'copyright' => [
                'fontSize' => '14px',
                'color' => '#666',
                'marginTop' => '-15px'
            ]
        ]
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