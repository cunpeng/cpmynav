<?php
// stats.php - 简化版本，移除文件锁
require_once 'config.php';

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
if (!file_exists(STATS_FILE)) {
    file_put_contents(STATS_FILE, json_encode($defaultStats));
    chmod(STATS_FILE, 0644);
}

// 读取统计数据
$statsContent = @file_get_contents(STATS_FILE);
$stats = $statsContent ? json_decode($statsContent, true) : $defaultStats;

if (!$stats) {
    $stats = $defaultStats;
}

$today = date('Ymd');
$currentIP = getClientIP();

// 每日数据初始化
if (!isset($stats['daily'][$today])) {
    $stats['daily'][$today] = 0;
}

// 同一IP 5分钟内不重复统计
$lastVisitTime = $stats['ips'][$currentIP] ?? 0;
if ($currentIP != $stats['last_ip'] || time() - $lastVisitTime > 300) {
    $stats['total']++;
    $stats['daily'][$today]++;
    $stats['last_ip'] = $currentIP;
    $stats['ips'][$currentIP] = time();
}

// 清理过期IP记录（保留7天）
foreach ($stats['ips'] as $ip => $timestamp) {
    if (time() - $timestamp > 604800) {
        unset($stats['ips'][$ip]);
    }
}

// 保存数据（简化写入，无文件锁）
file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));

// 暴露统计变量
$totalVisits = number_format($stats['total']);
$todayVisits = number_format($stats['daily'][$today] ?? 0);
?>