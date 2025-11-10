#!/bin/bash
# start.sh - 宝塔面板初始化脚本

echo "正在初始化导航站..."

# 创建数据目录
mkdir -p data/backups

# 设置权限
chmod 755 data/
chmod 755 data/backups/
chmod 644 data/data.json 2>/dev/null || true
chmod 644 data/stats.json 2>/dev/null || true

# 创建默认数据文件（如果不存在）
if [ ! -f data/data.json ]; then
    cat > data/data.json << 'EOF'
{
    "siteName": "我的导航站",
    "adminPassword": "12345678",
    "links": [],
    "pages": {},
    "footerCopyright": "© 我的导航站. All rights reserved."
}
EOF
    echo "已创建默认数据文件"
fi

if [ ! -f data/stats.json ]; then
    cat > data/stats.json << 'EOF'
{
    "total": 0,
    "daily": {},
    "last_ip": "",
    "ips": {}
}
EOF
    echo "已创建统计文件"
fi

echo "初始化完成！"
echo "请确保data目录有写权限：chown -R www:www data/"
echo "后台地址：您的域名/admin.php"
echo "默认密码：wucunpeng"