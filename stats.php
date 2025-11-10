<?php
// stats.php
// 引入配置文件
require_once 'config.php';

// 修改统计文件路径
$statsFile = STATS_FILE;

// 初始化数据结构
$defaultStats = [
    'total' => 0,
    'daily' => [
        date('Ymd') => 0
    ],
    'last_ip' => '',
    'ips' => []
];

// 安全获取客户端IP
function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

// 读取或初始化统计文件
if (!file_exists($statsFile)) {
    file_put_contents($statsFile, json_encode($defaultStats));
}

$fp = fopen($statsFile, 'r+');
if (flock($fp, LOCK_EX)) { // 排他锁
    $stats = json_decode(fread($fp, filesize($statsFile)), true) ?? $defaultStats;
    
    $today = date('Ymd');
    $currentIP = getClientIP();
    
    // 每日数据初始化
    if (!isset($stats['daily'][$today])) {
        $stats['daily'][$today] = 0;
    }
    
    // 同一IP 5分钟内不重复统计
    if ($currentIP != $stats['last_ip'] || time() - $stats['ips'][$currentIP] > 300) {
        $stats['total']++;
        $stats['daily'][$today]++;
        $stats['last_ip'] = $currentIP;
        $stats['ips'][$currentIP] = time();
    }
    
    // 清理过期IP记录（保留7天）
    foreach ($stats['ips'] as $ip => $timestamp) {
        if (time() - $timestamp > 604800) { // 7天
            unset($stats['ips'][$ip]);
        }
    }
    
    // 保存数据
    ftruncate($fp, 0);
    fseek($fp, 0);
    fwrite($fp, json_encode($stats, JSON_PRETTY_PRINT));
    flock($fp, LOCK_UN);
}
fclose($fp);

// 暴露统计变量
$totalVisits = number_format($stats['total']);
$todayVisits = number_format($stats['daily'][$today]);
?>