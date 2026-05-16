<?php
// backup.php
// 引入配置文件
require_once 'config.php';

// 保留最近7天的备份
$backupDir = BACKUP_DIR;
$maxBackups = 7;

// 确保备份目录存在
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$files = glob($backupDir . "*.json");
if (count($files) > $maxBackups) {
    usort($files, function($a, $b) {
        return filemtime($a) < filemtime($b);
    });
    for ($i = $maxBackups; $i < count($files); $i++) {
        unlink($files[$i]);
    }
}
?>