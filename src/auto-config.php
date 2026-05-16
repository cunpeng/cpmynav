<?php
require_once 'config.php';
require_once 'config.php';


header('Content-Type: text/html; charset=utf-8');


function is_baota_environment() {
    return is_dir('/www/server') && is_dir('/www/wwwroot');
}


function get_current_site_path() {
    return realpath(dirname(__FILE__));
}


function get_current_domain() {
    if (isset($_SERVER['HTTP_HOST'])) {
        return $_SERVER['HTTP_HOST'];
    }
    return 'localhost';
}

// 自动配置Nginx伪静态
function auto_configure_nginx() {
    $result = [];
    $current_dir = get_current_site_path();
    $domain = get_current_domain();
    
    // 检测Nginx配置文件
    $config_file = "/www/server/panel/vhost/nginx/{$domain}.conf";
    
    if (!file_exists($config_file)) {
        $result['error'] = "未找到域名 {$domain} 对应的Nginx配置文件";
        return $result;
    }
    
    // 备份原配置文件
    $backup_file = $config_file . '.backup.' . date('Ymd_His');
    if (!copy($config_file, $backup_file)) {
        $result['error'] = "配置文件备份失败";
        return $result;
    }
    $result['backup_file'] = $backup_file;
    
    // 读取配置文件内容
    $config_content = file_get_contents($config_file);
    
    // 伪静态规则
    $rewrite_rules = <<<'EOF'
    # 自动配置的伪静态规则 - 支持直接子页面访问
    location / {
        try_files $uri $uri/ /index.php?path=$uri;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-xxx.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    error_page 404 /index.php?path=404;
    error_page 500 502 503 504 /index.php?path=500;
EOF;
    
    // 如果已经配置过，先删除旧的配置
    $patterns = [
        '/\s*# 自动配置的伪静态规则.*?error_page 500 502 503 504 \/index\.php\?path=500;/s',
        '/\s*location \/ \{.*?^    \}/ms',
        '/\s*location ~ \\.php\$.*?^    \}/ms',
        '/\s*location ~ \/\.ht.*?^    \}/ms',
        '/\s*error_page 404.*?\n/',
        '/\s*error_page 500.*?\n/'
    ];
    
    foreach ($patterns as $pattern) {
        $config_content = preg_replace($pattern, '', $config_content);
    }
    
    // 在server块内插入配置
    if (strpos($config_content, 'server {') !== false) {
        $insert_point = strpos($config_content, 'server {') + 9;
        $config_content = substr($config_content, 0, $insert_point) . 
                         "\n    " . $rewrite_rules . 
                         substr($config_content, $insert_point);
    }
    
    // 写入配置文件
    if (file_put_contents($config_file, $config_content) === false) {
        $result['error'] = "配置文件写入失败";
        return $result;
    }
    
    // 设置目录权限
    chmod($current_dir, 0755);
    if (is_dir($current_dir . '/data')) {
        chmod($current_dir . '/data', 0755);
    }
    
    $result['success'] = true;
    $result['config_file'] = $config_file;
    
    return $result;
}

// 执行配置
$output = [];

if (is_baota_environment()) {
    $output['environment'] = '宝塔面板环境';
    $output['domain'] = get_current_domain();
    $output['site_path'] = get_current_site_path();
    
    $config_result = auto_configure_nginx();
    $output = array_merge($output, $config_result);
    
} else {
    $output['environment'] = '非宝塔面板环境';
    $output['message'] = '请手动配置伪静态规则';
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>自动配置工具 - 导航站</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: #f0f2f5; 
            margin: 0; 
            padding: 20px; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: #fff; 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #1a1a1a; 
            text-align: center; 
            margin-bottom: 2rem; 
        }
        .status { 
            padding: 1rem; 
            border-radius: 8px; 
            margin: 1rem 0; 
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            border: 1px solid #bee5eb; 
        }
        .btn { 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 0.8rem 1.5rem; 
            border-radius: 8px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            margin: 0.5rem; 
        }
        .btn:hover { 
            background: #0056b3; 
        }
        .restart-btn { 
            background: #28a745; 
        }
        .restart-btn:hover { 
            background: #1e7e34; 
        }
        .step { 
            margin: 1.5rem 0; 
            padding: 1rem; 
            background: #f8f9fa; 
            border-radius: 8px; 
        }
        .code { 
            background: #f1f3f4; 
            padding: 0.5rem; 
            border-radius: 4px; 
            font-family: monospace; 
            margin: 0.5rem 0; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 导航站自动配置工具</h1>
        
        <div class="step">
            <h3>📋 环境检测</h3>
            <div class="code">
                环境：<?php echo htmlspecialchars($output['environment']); ?><br>
                域名：<?php echo htmlspecialchars($output['domain'] ?? '未知'); ?><br>
                网站路径：<?php echo htmlspecialchars($output['site_path'] ?? '未知'); ?>
            </div>
        </div>
        
        <?php if (isset($output['error'])): ?>
        <div class="status error">
            <h3>❌ 配置失败</h3>
            <p><?php echo htmlspecialchars($output['error']); ?></p>
        </div>
        <?php elseif (isset($output['success'])): ?>
        <div class="status success">
            <h3>✅ 配置成功</h3>
            <p>Nginx伪静态规则已自动配置完成！</p>
            <p>配置文件：<?php echo htmlspecialchars($output['config_file']); ?></p>
            <?php if (isset($output['backup_file'])): ?>
            <p>备份文件：<?php echo htmlspecialchars($output['backup_file']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="step">
            <h3>🔧 下一步操作</h3>
            <p>需要手动重启Nginx服务使配置生效：</p>
            <div class="code">
                # 通过宝塔面板重启<br>
                1. 登录宝塔面板<br>
                2. 进入软件商店 → Nginx<br>
                3. 点击"重启"按钮<br><br>
                
                # 通过命令行重启<br>
                /etc/init.d/nginx reload
            </div>
            
            <div style="margin-top: 1rem;">
                <a href="/" class="btn">🏠 访问首页</a>
                <a href="/admin.php" class="btn">⚙️ 管理后台</a>
            </div>
        </div>
        
        <?php elseif (isset($output['message'])): ?>
        <div class="status info">
            <h3>ℹ️ 配置提示</h3>
            <p><?php echo htmlspecialchars($output['message']); ?></p>
        </div>
        
        <div class="step">
            <h3>📝 手动配置指南</h3>
            <p>请将以下配置添加到Nginx伪静态设置中：</p>
            <div class="code">
                location / {<br>
                &nbsp;&nbsp;&nbsp;&nbsp;try_files $uri $uri/ /index.php?path=$uri;<br>
                }<br><br>
                
                location ~ \.php$ {<br>
                &nbsp;&nbsp;&nbsp;&nbsp;fastcgi_pass unix:/tmp/php-cgi-xxx.sock;<br>
                &nbsp;&nbsp;&nbsp;&nbsp;fastcgi_index index.php;<br>
                &nbsp;&nbsp;&nbsp;&nbsp;fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;<br>
                &nbsp;&nbsp;&nbsp;&nbsp;include fastcgi_params;<br>
                &nbsp;&nbsp;&nbsp;&nbsp;fastcgi_read_timeout 300;<br>
                &nbsp;&nbsp;&nbsp;&nbsp;fastcgi_connect_timeout 300;<br>
                &nbsp;&nbsp;&nbsp;&nbsp;fastcgi_send_timeout 300;<br>
                }<br><br>
                
                location ~ /\.ht {<br>
                &nbsp;&nbsp;&nbsp;&nbsp;deny all;<br>
                }<br><br>
                
                error_page 404 /index.php?path=404;<br>
                error_page 500 502 503 504 /index.php?path=500;
            </div>
        </div>
        <?php endif; ?>
        
        <div class="step">
            <h3>✅ 验证配置</h3>
            <p>配置完成后，请测试以下功能：</p>
            <ol>
                <li>访问网站首页：<a href="/" target="_blank">首页测试</a></li>
                <li>在管理后台创建测试子页面</li>
                <li>访问子页面验证：http://<?php echo htmlspecialchars(get_current_domain()); ?>/测试页面</li>
                <li>检查子页面是否能正常显示</li>
            </ol>
        </div>
    </div>
</body>
</html>