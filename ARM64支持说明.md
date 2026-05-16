# ARM64架构支持说明

## 项目已支持ARM64架构

本项目已经进行了全面修改，现在支持在ARM64和AMD64架构上运行。

## 主要修改内容

### 1. Dockerfile改进
- 使用 `--platform=$BUILDPLATFORM` 参数支持多架构构建
- 设置时区环境变量 `TZ=Asia/Shanghai`
- 优化包管理器安装，使用 `--no-install-recommends` 减少镜像大小
- 添加ARM64兼容的包依赖

### 2. docker-compose.yml改进
- 配置多平台支持：`linux/amd64` 和 `linux/arm64`
- 启用时区设置
- 支持指定特定架构运行

### 3. 新增构建脚本
- `build-multiarch.sh` - Linux/macOS脚本
- `build-multiarch.ps1` - Windows PowerShell脚本

## 使用方法

### 多架构构建

#### Linux/macOS:
```bash
chmod +x build-multiarch.sh
./build-multiarch.sh
```

#### Windows:
```powershell
.\build-multiarch.ps1
```

### 本地运行

#### 默认架构运行:
```bash
docker-compose up -d
```

#### 指定ARM64架构运行:
```bash
docker-compose up -d --build --platform linux/arm64
```

#### 查看架构信息:
```bash
docker image inspect cunpeng/cpmynav:1.02 | grep Architecture
```

## 支持的架构
- ✅ `linux/amd64` - Intel/AMD 64位处理器
- ✅ `linux/arm64` - ARM 64位处理器（如Apple Silicon M1/M2、树莓派4等）

## 兼容性说明

### 基础镜像
- 使用官方 `php:8.2-fpm` 镜像，原生支持多架构
- Apache2和其他依赖包均可在ARM64架构上正常运行

### 应用程序
- PHP应用代码本身架构无关
- 所有依赖都是纯PHP扩展，无需架构特定编译

### 性能
- ARM64架构在容器化环境下性能表现良好
- 特别适合在Apple Silicon Mac、树莓派等设备上运行

## 注意事项

1. 确保Docker版本支持多架构构建（Docker Desktop 20.10+）
2. 第一次构建需要下载ARM64基础镜像，可能耗时较长
3. 如果遇到权限问题，确保脚本有执行权限

## 故障排除

### 构建失败
- 检查Docker daemon是否支持buildx
- 运行 `docker buildx ls` 确认多架构支持

### 运行错误
- 检查镜像是否包含ARM64架构
- 确认Docker版本支持多架构镜像拉取

### 性能问题
- ARM64架构在容器中通常性能良好
- 确保分配足够的内存和CPU资源