<?php
// backup.php
// 引入配置文件
require_once 'config.php';

session_start();
if(!isset($_SESSION['loggedin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('无权访问');
}

// 保留最近7天的备份
$backupDir = BACKUP_DIR;
$maxBackups = 7;

// 确保备份目录存在
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// 创建备份
$backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.json';
if (copy(DATA_FILE, $backupFile)) {
    $backupSuccess = true;
} else {
    $backupError = '备份创建失败';
}

// 清理旧备份
$files = glob($backupDir . "*.json");
if (count($files) > $maxBackups) {
    usort($files, function($a, $b) {
        return filemtime($a) < filemtime($b);
    });
    for ($i = $maxBackups; $i < count($files); $i++) {
        unlink($files[$i]);
    }
    $cleanedBackups = count($files) - $maxBackups;
}

// 获取备份文件列表
$backupFiles = [];
$files = glob($backupDir . "*.json");
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
foreach ($files as $file) {
    $backupFiles[] = [
        'filename' => basename($file),
        'size' => filesize($file),
        'time' => filemtime($file)
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据备份</title>
    <style>
        body { padding: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; padding: 10px; background: #f0fff0; border-radius: 5px; }
        .error { color: red; padding: 10px; background: #fff0f0; border-radius: 5px; }
        .backup-list { margin-top: 20px; }
        .backup-item { padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 5px; }
        .backup-actions { margin-top: 10px; }
        button { padding: 5px 10px; margin-right: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>数据备份管理</h1>
        <p><a href="admin.php">返回后台管理</a></p>
        
        <?php if(isset($backupSuccess)): ?>
            <div class="success">备份创建成功！</div>
        <?php endif; ?>
        
        <?php if(isset($backupError)): ?>
            <div class="error"><?= $backupError ?></div>
        <?php endif; ?>
        
        <?php if(isset($cleanedBackups)): ?>
            <div class="success">已清理 <?= $cleanedBackups ?> 个旧备份</div>
        <?php endif; ?>
        
        <div style="margin: 20px 0;">
            <button onclick="createBackup()">创建新备份</button>
            <button onclick="location.reload()">刷新列表</button>
        </div>
        
        <div class="backup-list">
            <h2>备份文件列表（保留最近 <?= $maxBackups ?> 个）</h2>
            <?php if(empty($backupFiles)): ?>
                <p>暂无备份文件</p>
            <?php else: ?>
                <?php foreach($backupFiles as $backup): ?>
                    <div class="backup-item">
                        <strong><?= $backup['filename'] ?></strong><br>
                        大小: <?= round($backup['size'] / 1024, 2) ?> KB | 
                        时间: <?= date('Y-m-d H:i:s', $backup['time']) ?>
                        <div class="backup-actions">
                            <button onclick="downloadBackup('<?= $backup['filename'] ?>')">下载</button>
                            <button onclick="restoreBackup('<?= $backup['filename'] ?>')">恢复</button>
                            <button onclick="deleteBackup('<?= $backup['filename'] ?>')" style="background: #dc3545; color: white;">删除</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function createBackup() {
            if(confirm('确定要创建新备份吗？')) {
                window.location.href = 'backup.php?action=create';
            }
        }
        
        function downloadBackup(filename) {
            window.location.href = 'backup.php?action=download&file=' + encodeURIComponent(filename);
        }
        
        function restoreBackup(filename) {
            if(confirm('确定要恢复此备份吗？当前数据将被覆盖！')) {
                window.location.href = 'backup.php?action=restore&file=' + encodeURIComponent(filename);
            }
        }
        
        function deleteBackup(filename) {
            if(confirm('确定要删除此备份吗？此操作不可恢复！')) {
                window.location.href = 'backup.php?action=delete&file=' + encodeURIComponent(filename);
            }
        }
    </script>
</body>
</html>

<?php
// 处理备份操作
if(isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'create':
            // 创建备份已在上面处理
            header('Location: backup.php');
            exit;
            
        case 'download':
            if(isset($_GET['file'])) {
                $file = BACKUP_DIR . $_GET['file'];
                if(file_exists($file)) {
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
                    readfile($file);
                    exit;
                }
            }
            break;
            
        case 'restore':
            if(isset($_GET['file'])) {
                $file = BACKUP_DIR . $_GET['file'];
                if(file_exists($file)) {
                    if(copy($file, DATA_FILE)) {
                        header('Location: backup.php?restore=success');
                    } else {
                        header('Location: backup.php?restore=error');
                    }
                    exit;
                }
            }
            break;
            
        case 'delete':
            if(isset($_GET['file'])) {
                $file = BACKUP_DIR . $_GET['file'];
                if(file_exists($file)) {
                    unlink($file);
                    header('Location: backup.php');
                    exit;
                }
            }
            break;
    }
}
?>