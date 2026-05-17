FROM php:8.2-apache

# 安装工具
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    tzdata \
    ca-certificates \
    iproute2 \
    nano \
    && docker-php-ext-install intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

ENV TZ=Asia/Shanghai

# 1. 修改 ports.conf：只监听 8821
RUN echo "Listen 8821" > /etc/apache2/ports.conf

# 2. 复制站点配置
COPY apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf

# 3. 启用站点，并启用 rewrite 模块
RUN a2ensite 000-default.conf && a2enmod rewrite

# 4. 设置 ServerName 避免启动警告
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 5. 创建数据目录
RUN mkdir -p /data/backups && \
    chown -R www-data:www-data /data && \
    chmod -R 775 /data

# 6. 复制 PHP 应用
COPY src/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 7. 只暴露 8821
EXPOSE 8821

# 8. 使用官方前台启动命令
CMD ["apache2-foreground"]
