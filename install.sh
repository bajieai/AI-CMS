#!/bin/bash

# AI-CMS 一键安装脚本
# 适用于Linux/macOS系统
# 使用方式: ./install.sh [--docker|--native|--init-db]
#
# 框架: ThinkPHP 8.1 (非Laravel，不使用artisan/migrate命令)
# 前端: Vue 3 + Vite (需要npm构建)

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
DB_DATABASE="${DB_DATABASE:-ai_cms}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-root123456}"
REDIS_HOST="${REDIS_HOST:-localhost}"
REDIS_PORT="${REDIS_PORT:-6379}"

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

# 检查PHP必需扩展 (ThinkPHP运行所需)
# 必需扩展列表: pdo_mysql(数据库) redis(缓存/会话) gd(图片处理)
#               bcmath(AI token计算) mbstring(多字节字符串)
#               xml(XML解析) zip(压缩) json(JSON处理)
check_php_extensions() {
    local required_exts=("pdo_mysql" "redis" "gd" "bcmath" "mbstring" "xml" "zip" "json")
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
        echo "  Windows (编辑php.ini):"
        echo "    取消对应 extension=${ext}.dll 的注释"
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
    sleep 10
    
    # 等待容器完全启动
    for i in {1..30}; do
        if docker exec aicms_mysql mysqladmin ping -h localhost -u root -proot123456 &> /dev/null; then
            print_success "MySQL服务已就绪"
            break
        fi
        echo -n "."
        sleep 2
    done
    echo ""
    
    # 创建数据库
    print_step "创建数据库..."
    docker exec -i aicms_mysql mysql -u root -proot123456 <<EOF
CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF
    print_success "数据库创建完成"
    
    # 执行迁移脚本
    print_step "执行数据库迁移..."
    docker exec -i aicms_mysql mysql -u root -proot123456 "$DB_DATABASE" < database/migrations/20260418_create_all_tables.sql
    print_success "数据库迁移完成"
    
    # 询问是否导入示例数据
    read -p "是否导入示例数据? (y/n): " IMPORT_SAMPLE
    if [[ "$IMPORT_SAMPLE" == "y" || "$IMPORT_SAMPLE" == "Y" ]]; then
        print_step "导入示例数据..."
        docker exec -i aicms_mysql mysql -u root -proot123456 "$DB_DATABASE" < database/seeds/sample_data.sql
        print_success "示例数据导入完成"
    fi
    
    print_success "Docker部署完成!"
    echo ""
    echo "访问地址: http://localhost"
    echo "后台地址: http://localhost/admin"
    echo "默认账号: admin / Admin@2026"
}

# 原生部署模式（适用于有运维经验的用户）
install_native() {
    print_step "开始原生部署模式..."
    
    # 检查PHP
    check_php
    if ! $PHP_INSTALLED; then
        print_error "PHP未安装，请先安装PHP 8.1+"
        exit 1
    fi
    
    # 检查PHP版本兼容性
    PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
    PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')
    if [[ "$PHP_MAJOR" -lt 8 ]] || ([[ "$PHP_MAJOR" -eq 8 ]] && [[ "$PHP_MINOR" -lt 1 ]]); then
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
    if ! $MYSQL_INSTALLED; then
        print_warning "MySQL客户端未安装，某些功能可能不可用"
    fi
    
    # 进入项目目录
    cd "$PROJECT_DIR"
    
    # 创建数据库
    print_step "创建数据库..."
    if $MYSQL_INSTALLED; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" <<EOF
CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF
        print_success "数据库创建完成"
    else
        print_warning "跳过数据库创建，请手动创建"
    fi
    
    # 复制.env文件 (使用ThinkPHP原生格式模板)
    print_step "配置环境变量..."
    if [ ! -f backend/.env ]; then
        cp backend/.env.example backend/.env
        print_success ".env文件已从模板创建"
        print_warning "请务必编辑 backend/.env 文件，修改以下配置项:"
        echo "  - DB_PASS     数据库密码"
        echo "  - REDIS_PASS   Redis密码(如有)"
        echo "  - JWT_SECRET   JWT密钥(必须改为随机字符串!)"
        echo "  - AI_DEEPSEEK_API_KEY  AI功能API Key"
    else
        print_warning "backend/.env文件已存在，跳过"
    fi
    
    # 安装Composer依赖
    print_step "安装Composer依赖(PHP后端包)..."
    if command -v composer &> /dev/null; then
        cd backend
        composer install --no-dev --optimize-autoloader
        cd ..
        print_success "Composer依赖安装完成"
    else
        print_error "Composer未安装，请先安装Composer"
        echo "下载地址: https://getcomposer.org/download/"
        exit 1
    fi
    
    # 构建前端 (Vue 3 + Vite 项目必须构建才能部署)
    print_step "构建前端资源(Vue 3 + Vite)..."
    if command -v npm &> /dev/null; then
        cd frontend
        npm install --prefer-offline
        npm run build
        cd ..
        
        # 将前端构建产物复制到后端公共目录
        if [ -d frontend/dist ]; then
            mkdir -p backend/public/assets
            cp -r frontend/dist/* backend/public/assets/
            print_success "前端构建完成，静态资源已复制到 backend/public/assets/"
        else
            print_warning "frontend/dist 目录不存在，npm run build 可能失败"
            print_warning "请手动检查 frontend/ 目录下的依赖和构建配置"
        fi
    else
        print_warning "npm未安装，跳过前端构建步骤"
        echo "请手动执行以下命令构建前端:"
        echo "  cd frontend && npm install && npm run build"
        echo "然后将 frontend/dist/ 内容复制到 backend/public/assets/"
    fi
    
    # 执行数据库初始化（SQL方式导入表结构，非Laravel migration）
    print_step "初始化数据库结构..."
    if $MYSQL_INSTALLED && [ -f database/migrations/20260418_create_all_tables.sql ]; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < database/migrations/20260418_create_all_tables.sql
        print_success "数据库表结构导入完成(17张表)"
        
        # 询问是否导入示例数据
        read -p "是否导入示例数据? (y/n): " IMPORT_SAMPLE
        if [[ "$IMPORT_SAMPLE" == "y" || "$IMPORT_SAMPLE" == "Y" ]]; then
            if [ -f database/seeds/sample_data.sql ]; then
                mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < database/seeds/sample_data.sql
                print_success "示例数据导入完成"
            else
                print_warning "未找到示例数据文件 database/seeds/sample_data.sql"
            fi
        fi
    else
        print_warning "无法自动导入数据库表结构，请手动执行:"
        echo "  mysql -h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p $DB_DATABASE \\"
        echo "    < database/migrations/20260418_create_all_tables.sql"
    fi
    
    # 设置目录权限 (ThinkPHP需要runtime子目录可写)
    print_step "设置目录权限..."
    if [[ "$OSTYPE" == "darwin"* ]] || [[ "$OSTYPE" == "linux-gnu"* ]]; then
        chmod -R 755 backend/runtime 2>/dev/null || true
        chmod -R 755 backend/public/uploads 2>/dev/null || true
        mkdir -p backend/runtime/cache backend/runtime/log backend/runtime/temp
        chmod -R 777 backend/runtime/cache backend/runtime/log backend/runtime/temp 2>/dev/null || true
        print_success "目录权限设置完成"
    else
        print_warning "非Linux/macOS系统，请手动设置 runtime/ 目录可写权限"
    fi
    
    # 输出部署完成信息
    print_success "原生部署准备完成!"
    echo ""
    echo "=========================================="
    echo "  后续操作步骤:"
    echo ""
    echo "  [必须] 1. 编辑 backend/.env 配置数据库连接信息"
    echo "          确保 DB_HOST/DB_PASS/DB_NAME 与实际环境一致"
    echo ""
    echo "  [必须] 2. 启动基础服务:"
    echo "          sudo systemctl start mysql redis-server"
    echo ""
    echo "  [选一] 3A. 开发模式启动(快速验证):"
    echo "              cd backend && php think run --port=8080"
    echo "              访问: http://localhost:8080"
    echo ""
    echo "          3B. 生产模式(Nginx反向代理):"
    echo "              参考配置: deploy/nginx/aicms.conf"
    echo "              sudo nginx -t && sudo systemctl reload nginx"
    echo "              访问: http://your-server-ip"
    echo ""
    echo "  默认账号: admin / Admin@2026"
    echo "=========================================="
}

# 仅初始化数据库
init_database() {
    print_step "开始初始化数据库..."
    
    cd "$PROJECT_DIR"
    
    # 检查MySQL客户端
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
    
    # 执行迁移脚本
    print_step "执行迁移脚本..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < database/migrations/20260418_create_all_tables.sql
    print_success "迁移脚本执行完成"
    
    # 导入示例数据
    print_step "导入示例数据..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < database/seeds/sample_data.sql
    print_success "示例数据导入完成"
    
    print_success "数据库初始化完成!"
}

# 显示帮助信息
show_help() {
    cat << EOF
AI-CMS 一键安装脚本 v2.0.0
框架: ThinkPHP 8.1 | 前端: Vue 3 + Vite

用法: ./install.sh [选项]

选项:
  --docker     Docker部署模式（推荐，一键搞定）
  --native     原生部署模式（需自行安装PHP/MySQL/Redis/Nginx）
  --init-db    仅初始化数据库（导入表结构和示例数据）
  --help       显示此帮助信息

环境变量:
  DB_HOST      数据库主机 (默认: localhost)
  DB_PORT      数据库端口 (默认: 3306)
  DB_DATABASE  数据库名称 (默认: ai_cms)
  DB_USERNAME  数据库用户名 (默认: root)
  DB_PASSWORD  数据库密码 (默认: root123456)
  REDIS_HOST   Redis主机 (默认: localhost)
  REDIS_PORT   Redis端口 (默认: 6379)

示例:
  ./install.sh --docker                    # Docker一键部署
  ./install.sh --native                    # 原生部署(需提前装好环境)
  ./install.sh --init-db                   # 只初始化数据库
  DB_HOST=192.168.1.100 ./install.sh --docker  # 指定远程数据库

注意事项:
  - 原生模式要求 PHP >= 8.0.5 (推荐 8.4+)
  - 需要以下PHP扩展: pdo_mysql, redis, gd, bcmath, mbstring, xml, zip
  - 前端构建需要 Node.js 18+ 和 npm
  - 生产环境建议使用 Nginx + PHP-FPM 部署

EOF
}

# 主函数
main() {
    echo "============================================"
    echo "       AI-CMS 一键安装脚本 v2.0.0"
    echo "    ThinkPHP 8.1 + Vue 3 + Element Plus"
    echo "============================================"
    echo ""
    
    check_root
    
    # 解析参数
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
            echo "或直接运行以下命令进行交互式安装:"
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
