# cpmynav 我的导航站

## 🚀 功能特点
- 📱 响应式设计，支持移动端
- 🔐 管理员后台管理
- 📊 访问统计功能
- 🔄 多级页面管理
- 📱 二维码生成与显示

## 🛠 技术栈
- PHP 8.2 + Apache
- Docker + Docker Compose
- 无需数据库

## 宝塔面板安装教程
- 选择LAMP环境（安装Apache）
- 新建网站，将文件全部复制到网站目录下
- 绑定域名访问后，会自动创建date文件夹

## 错误解决
- 如遇PHP8.2错误切换PHP8.0
- 如遇子页面打开404 nignx 请切换Apache nignx环境下无法访问子页面

## 管理员登录
- 访问：http://localhost/admin.php
- 默认密码：`12345678`

## 📁 ClawCloud Run 免费套餐 快速安装
- 新建APP项目
- Image Name填写cunpeng/cpmynav:1.01
- 建议CPU 0.2 +
- 建议Memory256M +
- 端口80
- Local Storage中Mount Path填写/data

## 🐳 Docker部署
```
docker pull cunpeng/cpmynav:1.02
```
Docker Run
```bash
docker run -d \
  --name cpmynav_app \
  -p 8821:80 \
  -v cpmynav_data:/data \
  -e TZ=Asia/Shanghai \  # 设置时区，可修改为其他时区
  --restart unless-stopped \
  cunpeng/cpmynav:1.02
```
```
- https://hub.docker.com/r/cunpeng/cpmynav
## 📦 使用 Docker Compose
```yaml
services:
  yyds:
    build: .
    image: cunpeng/cpmynav:1.02
    ports:
      - "8821:80"
    volumes:
      - yyds_data:/data
    environment:
#      - TZ=Asia/Shanghai  # 设置为上海时区，可根据需要修改
    restart: unless-stopped
    container_name: cpmynav_app

volumes:
  yyds_data:
```
启动服务
```
docker-compose up -d
```
访问应用：
网站首页：http://localhost:8821
管理后台：http://localhost:8821/admin.php
默认密码：12345678


## 📝 更新日志
- v1.00 (2025-05-22)
- v1.01 (2025-08-25)
- v1.02 (2025-11-11)

## ✨ 授权码
- 授权码仅限当日使用有效
- 交流售后QQ群333628217

## 👨‍💻 支持作者
![wechat-pay](https://github.com/user-attachments/assets/0926f261-1b00-4d8b-b9d3-49dcc980143b)

