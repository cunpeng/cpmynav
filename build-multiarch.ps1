# 多架构构建脚本 (Windows PowerShell版本)
# 支持ARM64和AMD64

Write-Host "开始构建多架构Docker镜像..." -ForegroundColor Green

# 检查Docker是否支持多架构构建
$buildxList = docker buildx ls
if ($buildxList -notmatch "multiarch") {
    Write-Host "创建多架构构建器..." -ForegroundColor Yellow
    docker buildx create --name multiarch --use
}

# 设置镜像标签
$IMAGE_NAME = "cunpeng/cpmynav"
$VERSION = "1.04arm64"

# 构建并推送多架构镜像
Write-Host "构建多架构镜像: $IMAGE_NAME`:$VERSION" -ForegroundColor Cyan
$result = docker buildx build `
    --platform linux/amd64,linux/arm64 `
    --tag $IMAGE_NAME`:$VERSION `
    --push `
    .

# 检查构建结果
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ 多架构镜像构建成功!" -ForegroundColor Green
    Write-Host "可用架构: linux/amd64, linux/arm64" -ForegroundColor Yellow
    Write-Host "镜像地址: $IMAGE_NAME`:$VERSION" -ForegroundColor Yellow
} else {
    Write-Host "❌ 镜像构建失败" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "本地运行测试:" -ForegroundColor Cyan
Write-Host "1. 使用默认架构运行: docker-compose up -d"
Write-Host "2. 指定ARM64架构运行: docker-compose up -d --build --platform linux/arm64"
Write-Host "3. 查看架构信息: docker image inspect $IMAGE_NAME`:$VERSION | Select-String Architecture"