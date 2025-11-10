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

### ClawCloud Run 免费套餐 快速安装
- 新建APP项目
- Image Name填写cunpeng/cpmynav:1.01
- 建议CPU 0.2 +
- 建议Memory256M +
- 端口80
- Local Storage中Mount Path填写/data

#### 📦 使用 Docker Compose
```yaml
version: '1.0'

services:
  cpmynav:
    image: cunpeng/cpmynav:1.01
    ports:
      - "8821:80"
    volumes:
      - cpmynav_data:/data
    restart: unless-stopped
    container_name: cpmynav_app

volumes:
  cpmynav_data:
```
## 📁 Docker项目结构
```
version: '3.8'

services:
  yyds:
    image: cunpeng/cpmynav:1.00  # 使用指定的镜像
    build: .  # 保留build用于本地开发
    ports:
      - "8821:80"  # 修改为8821端口
    volumes:
      - yyds_data:/data  # 使用命名卷
    restart: unless-stopped
    container_name: cpmynav_app  # 更新容器名称以匹配项目

volumes:
  yyds_data:  # 定义命名卷

```
### 使用 Docker Compose

`docker-compose.yml` 文件：

```yaml
version: '1.0'

services:
  cpmynav:
    image: cunpeng/cpmynav:1.01
    ports:
      - "8821:80"
    volumes:
      - cpmynav_data:/data
    restart: unless-stopped
    container_name: cpmynav_app

volumes:
  cpmynav_data:


bash
docker-compose up -d
## 📁 Docker项目结构
访问应用：
网站首页：http://localhost:8821
管理后台：http://localhost:8821/admin.php
默认密码：12345678

使用 Docker Run

使用 Docker Run
bash
docker run -d \
  --name cpmynav_app \
  -p 8821:80 \
  -v cpmynav_data:/data \
  --restart unless-stopped \
  cunpeng/cpmynav:1.00


## 🔧 部署
```bash
docker push cunpeng/cpmynav:1.01
```
📝 更新日志
v1.00 (2024-XX-XX)
✨ 初始版本发布

🐳 完整的 Docker 支持

📱 响应式界面设计
