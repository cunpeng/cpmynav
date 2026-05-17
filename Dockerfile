FROM php:8.2-cli

# 安装 Apache2 和必要模块
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    libapache2-mod-php8.2 \
    libicu-dev \
    tzdata \
    ca-certificates \
    iproute2 \
    nano \
    && docker-php-ext-install intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

ENV TZ=Asia/Shanghai

# 配置 Apache：只监听 8821
RUN echo "Listen 8821" > /etc/apache2/ports.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && a2enmod rewrite

# 复制虚拟主机配置
COPY apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default.conf

# 创建数据目录
RUN mkdir -p /data/backups && \
    chown -R www-data:www-data /data && \
    chmod -R 775 /data

# 复制 PHP 应用
COPY src/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html/ && \
    find /var/www/html/ -type d -exec chmod 755 {} \; && \
    find /var/www/html/ -type f -exec chmod 644 {} \;

# 复制启动脚本
COPY start.sh /start.sh
RUN chmod +x /start.sh

# 只暴露 8821
EXPOSE 8821

# 启动
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
