FROM php:8.2-apache

# 安装必要的工具
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    tzdata \
    ca-certificates \
    iproute2 \
    nano \
    && docker-php-ext-install intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 设置时区
ENV TZ=Asia/Shanghai

# Apache配置
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && echo "Listen 8821" >> /etc/apache2/ports.conf \
    && a2enmod rewrite

# 复制Apache配置
COPY apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf

# 创建数据目录
RUN mkdir -p /data/backups && \
    chown -R www-data:www-data /data && \
    chmod -R 775 /data

# 复制应用程序文件
COPY src/ /var/www/html/

# 设置web目录权限
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 复制启动脚本并修复换行符
COPY start.sh /start.sh
RUN sed -i 's/\r$//' /start.sh && chmod +x /start.sh

# 暴露端口
EXPOSE 8821

# 使用启动脚本
CMD ["/start.sh"]
