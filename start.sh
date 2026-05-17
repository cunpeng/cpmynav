#!/bin/bash
# 启动脚本 - 信号转发处理

# 设置时区
ln -sf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime
echo "Asia/Shanghai" > /etc/timezone
echo "date.timezone = Asia/Shanghai" > /usr/local/etc/php/conf.d/timezone.ini

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

chmod 644 /data/data.json /data/stats.json

# 启动 Apache（前台运行）
echo "启动Apache服务器..."
apache2-foreground &

# 记录PID
APACHE_PID=$!

# 信号转发函数
forward_signal() {
    echo "收到停止信号，正在关闭 Apache..."
    kill -TERM $APACHE_PID
    wait $APACHE_PID
    exit 0
}

# 捕获 SIGTERM 和 SIGINT
trap forward_signal SIGTERM SIGINT

# 等待 Apache 退出
wait $APACHE_PID