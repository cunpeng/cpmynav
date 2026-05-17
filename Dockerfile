FROM php:8.2-cli

# 安装 Apache
RUN apk add --no-cache apache2 apache2-proxy apache2-mod-php82 \
    && ln -sf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo "Asia/Shanghai" > /etc/timezone

ENV TZ=Asia/Shanghai

# 配置 Apache：只监听 8821
RUN sed -i 's/Listen 80/Listen 8821/g' /etc/apache2/httpd.conf \
    && sed -i 's/:80>/:8821>/g' /etc/apache2/httpd.conf \
    && sed -i 's/DocumentRoot "\/var\/www\/localhost\/htdocs"/DocumentRoot "\/var\/www\/html"/g' /etc/apache2/httpd.conf

# 复制虚拟主机配置
COPY apache-config/000-default.conf /etc/apache2/conf.d/000-default.conf

# 创建数据目录
RUN mkdir -p /data/backups \
    && chown -R apache:apache /data \
    && chmod -R 775 /data

# 复制 PHP 应用
COPY src/ /var/www/html/
RUN chown -R apache:apache /var/www/html/ \
    && find /var/www/html/ -type d -exec chmod 755 {} \; \
    && find /var/www/html/ -type f -exec chmod 644 {} \;

# 复制启动脚本
COPY start.sh /start.sh
RUN chmod +x /start.sh

# 只暴露 8821
EXPOSE 8821

# 启动
CMD ["/start.sh"]
