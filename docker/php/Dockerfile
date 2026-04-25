FROM php:8.4-fpm-alpine

# 安装系统依赖
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    libxml2-dev \
    freetype-dev \
    zip \
    unzip \
    oniguruma-dev \
    autoconf \
    g++ \
    make

# 安装PHP扩展
# 注意：PHP 8.0+ 已内置：json, ctype, session, tokenizer（无需单独安装）
# pdo 通过驱动安装，不需要单独安装 pdo 扩展本身
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    gd \
    zip \
    bcmath \
    opcache \
    mbstring \
    xml

# 安装Redis扩展
RUN pecl install redis && docker-php-ext-enable redis

# 安装Composer（避免依赖composer镜像，直接下载）
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# 配置PHP-FPM（覆盖默认配置以支持Docker网络）
COPY www.conf /usr/local/etc/php-fpm.d/zzz-custom.conf

# 设置工作目录
WORKDIR /var/www/html

# 确保 runtime 目录有正确的写入权限
RUN mkdir -p runtime/session runtime/admin/log && chmod -R 777 runtime

# 设置时区
RUN echo "Asia/Shanghai" > /etc/timezone \
    && ln -sf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime

# 暴露端口
EXPOSE 9000

# 启动PHP-FPM
CMD ["php-fpm"]
