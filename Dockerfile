FROM --platform=$BUILDPLATFORM php:8.2-fpm

# 设置时区变量（ARM兼容性）
ENV TZ=Asia/Shanghai

# 更新包管理器并安装ARM64兼容的Apache2和网络工具
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    libicu-dev \
    tzdata \
    ca-certificates \
    iproute2 \
    && docker-php-ext-install intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Apache配置
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN a2enmod rewrite
RUN a2enmod proxy_fcgi
RUN a2enmod proxy
RUN a2enmod proxy_http

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

# 复制启动脚本
COPY start.sh /start.sh
RUN chmod +x /start.sh

# 暴露端口
EXPOSE 80

# 使用启动脚本
CMD ["/start.sh"]