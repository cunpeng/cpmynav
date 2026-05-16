# CPMYNAV智能导航建站管理系统

## 🚀 功能特点
- 📱 响应式设计，支持移动端
- 🔐 管理员后台管理
- 📊 访问统计功能
- 🔄 多级页面管理
- 📱 二维码生成与显示

## 🛠 技术栈
- PHP 8.2 + Apache
- Docker + Docker Compose
- 免数据库

## 宝塔面板安装教程
- 选择LAMP环境（安装Apache）
- 新建网站，将文件全部复制到网站目录下
- 访问后，会自动创建date文件夹
- Apache环境下 在根目录的 .htaccess 中添加：
```
<FilesMatch "^(data|data\.json|stats\.json)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```
- Nginx环境下
 打开宝塔面板
网站 → 设置 → 配置文件
在 server { ... } 块内， location / { ... } 之前添加：
```
# 保护敏感数据目录
location ~ ^/data/(.*) {
    deny all;
    return 403;
}

# 保护备份目录
location ~ ^/data/backups/(.*) {
    deny all;
    return 403;
}

# 保护配置文件（如果有暴露风险）
location ~ ^/src/config_fixed.php$ {
    deny all;
    return 403;
}
```
伪静态设置
```
location / {
    try_files $uri $uri/ /index.php?path=$1;
}
```

## 错误解决
- 如遇PHP8.2错误切换PHP8.0
- 如遇子页面打开404 nignx 请切换Apache 
- nignx环境下无法访问子页面请设置伪静态

## 管理员登录
- 访问：http://localhost/admin.php
- 默认用户：`admin`
- 默认密码：`12345678`

## 数据迁移
- 新网站文件迁移文件后，删除data文件，重新访问域名，重新生成data文件，复制原有数据data.json文件替换数据即可

## 📁飞牛fnOS ARM OECT 安装指南
- Docker镜像仓库搜索cunpeng/cpmynav下载安装
- 端口设置8821 80 TCP
- 储存位置自定义
- 安装成功后主机ip:8821打开即可
- 特殊说明 数据迁移 不能直接替换文件 把数据复制粘贴即可

## 📁 ClawCloud Run 免费套餐 快速安装
- 新建APP项目
- Image Name填写cunpeng/cpmynav:1.1
- 建议CPU 0.2 +
- 建议Memory256M +
- 端口80
- Local Storage中Mount Path填写/data

## 🐳 Docker部署
```bash
docker pull cunpeng/cpmynav:1.1
```

Docker Run
```
docker run -d --name cpmynav_app -p 8821:80 -v cpmynav_data:/data -e TZ=Asia/Shanghai --restart unless-stopped cunpeng/cpmynav:1.1
```

## 📦 使用 Docker Compose
```yaml
services:
  services:
  cpmynav:
    build: .
    image: cunpeng/cpmynav:1.1
    ports:
      - "8821:80"
    volumes:
      - cpmynav_data:/data
    environment:
      - TZ=Asia/Shanghai
    restart: unless-stopped
    container_name: cpmynav_app

volumes:
  cpmynav_data:
```
启动服务
```
docker-compose up -d
```
访问应用：
- 网站首页：http://localhost:8821
- 管理后台：http://localhost:8821/admin.php
- 默认用户：admin
- 默认密码：12345678

## 📝 更新日志
- v1.00 (2025-05-22)
- v1.01 (2025-08-25)
- v1.02 (2025-09-22)
- v1.03 (2025-11-11)
- v1.0  (2026-01-22)
- v1.1  (2026-05-13)小修正

## ✨ 授权码
- 授权码仅限当日使用有效
- 交流售后QQ群333628217

## ⭐ 项目地址
- https://github.com/cunpeng/cpmynav

## 👨‍💻 赞赏作者
![wechat-pay](https://github.com/user-attachments/assets/0926f261-1b00-4d8b-b9d3-49dcc980143b)



