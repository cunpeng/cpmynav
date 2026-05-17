#!/bin/sh
# 启动脚本

ln -sf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime
echo "Asia/Shanghai" > /etc/timezone

# 数据目录
mkdir -p /data/backups
chown -R apache:apache /data
chmod 755 /data /data/backups

# 默认数据文件
if [ ! -f /data/data.json ]; then
    echo '{"siteName":"CPMYNAV智能导航建站管理系统","adminPassword":"12345678","links":[],"pages":{},"footerCopyright":"© CPMYNAV智能导航建站管理系统. All rights reserved.","showStats":true,"showStatsInAdmin":true}' > /data/data.json
    chown apache:apache /data/data.json
fi

if [ ! -f /data/stats.json ]; then
    echo '{"total":0,"daily":{},"last_ip":"","ips":{}}' > /data/stats.json
    chown apache:apache /data/stats.json
fi

chmod 644 /data/data.json /data/stats.json

# 启动 Apache
exec httpd -D FOREGROUND
