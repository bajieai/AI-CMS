#!/bin/bash

# AI-CMS V2.0 一键安装脚本
# 适用于Linux/macOS系统
# 使用方式: ./install.sh [--docker|--native|--init-db]
#
# 框架: ThinkPHP 8.1 (多应用模式，非Laravel)

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 配置变量
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-aicms_v2}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-root123456}"

# 打印函数
print_step() {
    echo -e "${BLUE}[步骤]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[成功]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[警告]${NC} $1"
}

print_error() {
    echo -e "${RED}[错误]${NC} $1"
}

# 检查是否为root用户
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_warning "建议使用root用户运行此脚本以获得最佳体验"
    fi
}

# 检查Docker环境
check_docker() {
    if command -v docker &> /dev/null && command -v docker-compose &> /dev/null; then
        return 0
    fi
    return 1
}

# 检查PHP环境
check_php() {
    if command -v php &> /dev/null; then
        PHP_INSTALLED=true
        PHP_CURRENT_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
        print_success "检测到PHP版本: $PHP_CURRENT_VERSION"
    else
        PHP_INSTALLED=false
        print_warning "未检测到PHP"
    fi
}

# 检查PHP必需扩展
check_php_extensions() {
    local required_exts=("pdo_mysql" "gd" "bcmath" "mbstring" "xml" "zip" "json")
    local missing_exts=()

    for ext in "${required_exts[@]}"; do
        if ! php -m 2>/dev/null | grep -qi "^${ext}$"; then
            missing_exts+=("$ext")
        fi
    done

    if [ ${#missing_exts[@]} -eq 0 ]; then
        print_success "所有必需的PHP扩展已安装"
        return 0
    else
        print_error "缺少以下PHP扩展: ${missing_exts[*]}"
        echo ""
        echo "请执行以下命令安装缺失的扩展:"
        echo "  Ubuntu/Debian:"
        for ext in "${missing_exts[@]}"; do
            echo "    sudo apt install php${PHP_CURRENT_VERSION}-${ext} 2>/dev/null || sudo apt install php-${ext}"
        done
        echo ""
        echo "  CentOS/RHEL (使用Remi源):"
        for ext in "${missing_exts[@]}"; do
            echo "    sudo yum install php${PHP_CURRENT_VERSION}-${ext}"
        done
        echo ""
        return 1
    fi
}

# 检查MySQL环境
check_mysql() {
    if command -v mysql &> /dev/null; then
        MYSQL_INSTALLED=true
        print_success "检测到MySQL客户端"
    else
        MYSQL_INSTALLED=false
        print_warning "未检测到MySQL客户端"
    fi
}

# Docker部署模式（推荐）
install_docker() {
    print_step "开始Docker部署模式..."

    cd "$PROJECT_DIR"

    # 检查Docker环境
    if ! check_docker; then
        print_error "Docker或Docker Compose未安装"
        echo "请先安装Docker: https://docs.docker.com/get-docker/"
        exit 1
    fi

    print_step "启动Docker容器..."
    docker-compose up -d --build

    # 等待MySQL就绪
    print_step "等待MySQL服务就绪..."
    for i in {1..30}; do
        if docker exec aicms_mysql mysqladmin ping -h localhost -u root -proot123456 &> /dev/null; then
            print_success "MySQL服务已就绪"
            break
        fi
        echo -n "."
        sleep 2
    done
    echo ""

    # 安装Composer依赖
    print_step "安装Composer依赖..."
    docker exec -i aicms_php composer install --no-dev --optimize-autoloader

    print_success "Docker部署完成!"
    echo ""
    echo "=========================================="
    echo "  后续操作:"
    echo ""
    echo "  1. 访问安装向导完成数据库初始化:"
    echo "     http://localhost:3000/install"
    echo ""
    echo "  2. 安装完成后访问:"
    echo "     前台: http://localhost:3000"
    echo "     后台: http://localhost:3000/admin"
    echo "=========================================="
}

# 原生部署模式
install_native() {
    print_step "开始原生部署模式..."

    # 检查PHP
    check_php
    if ! $PHP_INSTALLED; then
        print_error "PHP未安装，请先安装PHP 8.2+"
        exit 1
    fi

    # 检查PHP版本
    PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
    PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')
    if [[ "$PHP_MAJOR" -lt 8 ]] || ([[ "$PHP_MAJOR" -eq 8 ]] && [[ "$PHP_MINOR" -lt 0 ]]); then
        print_error "ThinkPHP 8.1 需要 PHP >= 8.0.5，当前版本: $PHP_MAJOR.$PHP_MINOR"
        exit 1
    fi

    # 检查PHP扩展
    print_step "检查PHP扩展..."
    check_php_extensions || {
        print_error "请先安装缺失的PHP扩展后再运行此脚本"
        exit 1
    }

    # 检查MySQL
    check_mysql

    cd "$PROJECT_DIR"

    # 配置.env
    print_step "配置环境变量..."
    if [ ! -f .env ]; then
        print_error ".env文件不存在"
        exit 1
    fi
    print_success ".env文件已存在，请确认数据库配置正确"

    # 安装Composer依赖
    print_step "安装Composer依赖..."
    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader
        print_success "Composer依赖安装完成"
    else
        print_error "Composer未安装，请先安装Composer"
        echo "下载地址: https://getcomposer.org/download/"
        exit 1
    fi

    # 创建数据库
    print_step "创建数据库..."
    if $MYSQL_INSTALLED; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" <<EOF
CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF
        print_success "数据库创建完成"
    else
        print_warning "请手动创建数据库: $DB_DATABASE"
    fi

    # 设置目录权限
    print_step "设置运行时目录权限..."
    mkdir -p runtime/cache runtime/log runtime/temp public/uploads
    if [[ "$OSTYPE" == "darwin"* ]] || [[ "$OSTYPE" == "linux-gnu"* ]]; then
        chmod -R 777 runtime/ 2>/dev/null || true
        chmod -R 777 public/uploads 2>/dev/null || true
        print_success "目录权限设置完成"
    else
        print_warning "非Linux/macOS系统，请手动设置 runtime/ 目录可写权限"
    fi

    # 输出完成信息
    print_success "原生部署准备完成!"
    echo ""
    echo "=========================================="
    echo "  后续操作步骤:"
    echo ""
    echo "  [必须] 1. 编辑 .env 配置数据库连接信息"
    echo ""
    echo "  [必须] 2. 访问安装向导完成数据库初始化:"
    echo "          php think run --port=8080"
    echo "          然后浏览器打开 http://localhost:8080/install"
    echo ""
    echo "  [或者] 3. 生产模式(Nginx+PHP-FPM):"
    echo "          参考配置: deploy/nginx/aicms.conf"
    echo "          访问: http://your-server-ip/install"
    echo "=========================================="
}

# 仅初始化数据库
init_database() {
    print_step "开始初始化数据库..."

    cd "$PROJECT_DIR"

    if ! command -v mysql &> /dev/null; then
        print_error "MySQL客户端未安装"
        exit 1
    fi

    # 创建数据库
    print_step "创建数据库..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" <<EOF
CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF
    print_success "数据库创建完成"

    # 执行建表脚本
    print_step "执行建表脚本..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < database/migrations/install.sql
    print_success "数据库初始化完成（8张表+默认数据）"

    print_success "数据库初始化完成!"
    echo ""
    echo "=========================================="
    echo "  默认管理员: admin / admin123"
    echo "  请访问 /install 完成Web安装向导"
    echo "=========================================="
}

# 显示帮助信息
show_help() {
    cat << EOF
AI-CMS V2.0 一键安装脚本
框架: ThinkPHP 8.1 (多应用模式)

用法: ./install.sh [选项]

选项:
  --docker     Docker部署模式（推荐）
  --native     原生部署模式（需自行安装PHP/MySQL/Nginx）
  --init-db    仅初始化数据库（导入表结构和默认数据）
  --help       显示此帮助信息

环境变量:
  DB_HOST      数据库主机 (默认: localhost)
  DB_PORT      数据库端口 (默认: 3306)
  DB_DATABASE  数据库名称 (默认: aicms_v2)
  DB_USERNAME  数据库用户名 (默认: root)
  DB_PASSWORD  数据库密码 (默认: root123456)

注意事项:
  - 原生模式要求 PHP >= 8.0.5 (推荐 8.2+)
  - 需要PHP扩展: pdo_mysql, gd, bcmath, mbstring, xml, zip
  - Docker模式会自动启动所有服务并通过Web安装向导初始化
  - 原生模式需手动访问 /install 完成安装向导
EOF
}

# 主函数
main() {
    echo "============================================"
    echo "       AI-CMS V2.0 一键安装脚本"
    echo "       ThinkPHP 8.1 多应用模式"
    echo "============================================"
    echo ""

    check_root

    case "${1:-}" in
        --docker)
            install_docker
            ;;
        --native)
            install_native
            ;;
        --init-db)
            init_database
            ;;
        --help|-h)
            show_help
            ;;
        *)
            echo "请指定安装模式:"
            echo "  --docker  Docker部署模式（推荐）"
            echo "  --native  原生部署模式（高级用户）"
            echo "  --init-db 仅初始化数据库"
            echo "  --help    显示帮助信息"
            echo ""
            read -p "是否使用Docker部署? (y/n): " USE_DOCKER
            if [[ "$USE_DOCKER" == "y" || "$USE_DOCKER" == "Y" ]]; then
                install_docker
            else
                install_native
            fi
            ;;
    esac
}

main "$@"
