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

## ClawCloud Run 免费套餐 快速安装
- 新建APP项目
- Image Name填写cunpeng/yyds:1.42
- 建议CPU 0.2 +
- 建议Memory256M +
- 端口80
- Local Storage中Mount Path填写/data

## 📦 Docker快速开始

- cunpeng/yyds:1.42

### 使用 Docker Compose
```bash
# 克隆项目
git clone https://github.com/cunpeng/cpmy.git

# 启动服务
docker-compose up -d

# 访问网站
http://localhost:80
```

### 管理员登录
- 访问：http://localhost:80/admin.php
- 默认密码：`12345678`

## 📁 Docker项目结构
```
yyds/
├── docker-compose.yml    # Docker编排配置
├── Dockerfile           # 镜像构建文件
├── start.sh            # 启动脚本
├── src/                # 源代码目录
└── apache-config/      # Apache配置
```

## 🔧 部署
```bash
docker push cunpeng/cpmynav:1.00
```
