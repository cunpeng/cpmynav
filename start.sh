#!/bin/bash
# 启动脚本 - 支持Apache和Nginx双Web服务器

# 设置时区（如果设置了TZ环境变量）
if [ -n "$TZ" ]; then
    echo "设置时区: $TZ"
    ln -sf /usr/share/zoneinfo/$TZ /etc/localtime
    echo $TZ > /etc/timezone
    echo "date.timezone = $TZ" > /usr/local/etc/php/conf.d/timezone.ini
fi

# 确保数据目录和文件存在
mkdir -p /data/backups
chown -R www-data:www-data /data
chmod 755 /data /data/backups

# 创建默认数据文件（如果不存在）
if [ ! -f /data/data.json ]; then
    echo '{"siteName":"CPMYNAV智能导航建站管理系统","adminPassword":"12345678","links":[],"pages":{},"footerCopyright":"© CPMYNAV智能导航建站管理系统. All rights reserved.","showStats":true,"showStatsInAdmin":true}' > /data/data.json
    chown www-data:www-data /data/data.json
fi

if [ ! -f /data/stats.json ]; then
    echo '{"total":0,"daily":{},"last_ip":"","ips":{}}' > /data/stats.json
    chown www-data:www-data /data/stats.json
fi

# 设置文件权限
chmod 644 /data/data.json /data/stats.json

# 根据环境变量选择Web服务器
WEB_SERVER=${WEB_SERVER:-apache}

echo "启动Web服务器: $WEB_SERVER"

# 启动PHP-FPM
php-fpm -D

# 等待PHP-FPM完全启动（最多等待10秒）
echo "等待PHP-FPM启动..."
for i in {1..10}; do
    if ss -tln | grep -q ':9000'; then
        echo "PHP-FPM已启动，端口9000可访问"
        break
    fi
    echo "等待PHP-FPM启动... ($i/10)"
    sleep 1
    if [ $i -eq 10 ]; then
        echo "警告: PHP-FPM启动超时，继续启动Apache..."
    fi
done

# 根据选择的服务器启动相应的Web服务器
case "$WEB_SERVER" in
    "apache")
        echo "启动Apache服务器..."
        # 配置Apache以使用PHP-FPM
        echo "<FilesMatch \.php$>
            SetHandler "proxy:fcgi://127.0.0.1:9000"
        </FilesMatch>" > /etc/apache2/conf-available/php-fpm.conf
        a2enconf php-fpm
        apachectl -D FOREGROUND
        ;;
    "nginx")
        echo "启动Nginx服务器..."
        nginx -g "daemon off;"
        ;;
    *)
        echo "错误: 未知的WEB_SERVER值 '$WEB_SERVER'，支持的值为 'apache' 或 'nginx'"
        exit 1
        ;;
esac