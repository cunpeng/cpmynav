#!/bin/bash
# 多架构构建脚本 - 支持ARM64和AMD64

echo "开始构建多架构Docker镜像..."

# 检查Docker是否支持多架构构建
if ! docker buildx ls | grep -q "multiarch"; then
    echo "创建多架构构建器..."
    docker buildx create --name multiarch --use
fi

# 设置镜像标签
IMAGE_NAME="cunpeng/cpmynav"
VERSION="1.04arm64"

# 构建并推送多架构镜像
echo "构建多架构镜像: $IMAGE_NAME:$VERSION"
docker buildx build \
    --platform linux/amd64,linux/arm64 \
    --tag $IMAGE_NAME:$VERSION \
    --push \
    .

# 检查构建结果
if [ $? -eq 0 ]; then
    echo "✅ 多架构镜像构建成功!"
    echo "可用架构: linux/amd64, linux/arm64"
    echo "镜像地址: $IMAGE_NAME:$VERSION"
else
    echo "❌ 镜像构建失败"
    exit 1
fi

echo ""
echo "本地运行测试:"
echo "1. 使用默认架构运行: docker-compose up -d"
echo "2. 指定ARM64架构运行: docker-compose up -d --build --platform linux/arm64"
echo "3. 查看架构信息: docker image inspect $IMAGE_NAME:$VERSION | grep Architecture"