@echo off
chcp 65001 > nul
setlocal enabledelayedexpansion

:: AI-CMS Windows安装脚本 v2.0.0
:: 框架: ThinkPHP 8.1 (非Laravel)
:: 使用方式: install.bat [--docker^|--native^|--init-db]

set "SCRIPT_DIR=%~dp0"
set "PROJECT_DIR=%SCRIPT_DIR%"

:: 配置变量
set "DB_HOST=localhost"
set "DB_PORT=3306"
set "DB_DATABASE=ai_cms"
set "DB_USERNAME=root"
set "DB_PASSWORD=root123456"
set "REDIS_HOST=localhost"
set "REDIS_PORT=6379"

:: 颜色支持 (Windows 10+)
reg query "HKCU\Console" /v VirtualTerminalLevel 2>nul >nul || (
    reg add "HKCU\Console" /v VirtualTerminalLevel /t REG_DWORD /d 1 /f >nul 2>&1
)

:: 主入口
goto :main

:::::::::::::::::::::::::::::::::::::
::  检查函数
:::::::::::::::::::::::::::::::::::::

:check_docker
where docker >nul 2>&1
if !errorlevel! equ 0 (
    where docker-compose >nul 2>&1
    if !errorlevel! equ 0 (
        set "DOCKER_AVAILABLE=1"
        goto :eof
    )
)
set "DOCKER_AVAILABLE=0"
goto :eof

:check_php
where php >nul 2>&1
if !errorlevel! equ 0 (
    set "PHP_INSTALLED=1"
    for /f "delims=" %%i in ('php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2^>nul') do set "PHP_VERSION=%%i"
    echo [成功] 检测到PHP版本: !PHP_VERSION!
) else (
    set "PHP_INSTALLED=0"
    echo [警告] 未检测到PHP
)
goto :eof

:check_php_extensions
echo [步骤] 检查PHP必需扩展...
set "MISSING_EXTS="

:: 检查每个必需扩展
for %%E in (pdo_mysql redis gd bcmath mbstring xml zip json) do (
    php -m 2>nul | findstr /i "^%%E$" >nul 2>&1
    if errorlevel 1 (
        if "!MISSING_EXTS!"=="" (
            set "MISSING_EXTS=%%E"
        ) else (
            set "MISSING_EXTS=!MISSING_EXTS!, %%E"
        )
    )
)

if "!MISSING_EXTS!"=="" (
    echo [成功] 所有必需的PHP扩展已安装
    goto :eof
) else (
    echo [错误] 缺少以下PHP扩展: !MISSING_EXTS!
    echo.
    echo 请安装缺失的扩展后重试。Windows下需要编辑 php.ini 文件：
    echo 取消对应 extension=%%E.dll 的注释，然后重启Web服务器。
    exit /b 1
)

:check_mysql
where mysql >nul 2>&1
if !errorlevel! equ 0 (
    set "MYSQL_INSTALLED=1"
    echo [成功] 检测到MySQL客户端
) else (
    set "MYSQL_INSTALLED=0"
    echo [警告] 未检测到MySQL客户端
)
goto :eof

:::::::::::::::::::::::::::::::::::::
::  Docker部署模式（推荐）
:::::::::::::::::::::::::::::::::::::

:install_docker
echo.
echo [步骤] 开始Docker部署模式...
echo.

cd /d "%PROJECT_DIR%"

call :check_docker
if "!DOCKER_AVAILABLE!"=="0" (
    echo [错误] Docker或Docker Compose未安装
    echo 请先安装Docker: https://docs.docker.com/get-started/install/
    pause
    goto :end
)

echo [步骤] 启动Docker容器...
docker-compose up -d --build

echo [步骤] 等待MySQL服务就绪...
timeout /t 10 /nobreak > nul

echo [步骤] 创建数据库...
docker exec -i aicms_mysql mysql -u root -proot123456 -e "CREATE DATABASE IF NOT EXISTS `%DB_DATABASE%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo [步骤] 执行数据库迁移...
docker exec -i aicms_mysql mysql -u root -proot123456 %DB_DATABASE% < "%PROJECT_DIR%database\migrations\20260418_create_all_tables.sql"

echo.
set /p IMPORT_SAMPLE="是否导入示例数据? (Y/N): "
if /i "%IMPORT_SAMPLE%"=="Y" (
    echo [步骤] 导入示例数据...
    docker exec -i aicms_mysql mysql -u root -proot123456 %DB_DATABASE% < "%PROJECT_DIR%database\seeds\sample_data.sql"
)

echo.
echo ============================================
echo [成功] Docker部署完成!
echo ============================================
echo 访问地址: http://localhost
echo 后台地址: http://localhost/admin
echo 默认账号: admin / Admin@2026
echo ============================================
goto :end

:::::::::::::::::::::::::::::::::::::
::  原生部署模式（高级用户）
:::::::::::::::::::::::::::::::::::::

:install_native
echo.
echo [步骤] 开始原生部署模式...
echo.

call :check_php
if "%PHP_INSTALLED%"=="0" (
    echo [错误] PHP未安装，请先安装PHP 8.1+
    pause
    goto :end
)

:: 检查PHP版本兼容性
for /f "delims=" %%v in ('php -r "echo PHP_MAJOR_VERSION;"') do set "PHP_MAJ=%%v"
for /f "delims=" %%v in ('php -r "echo PHP_MINOR_VERSION;"') do set "PHP_MIN=%%v"
if %PHP_MAJ% LSS 8 (
    echo [错误] ThinkPHP 8.1 需要 PHP >= 8.0.5, 当前版本: %PHP_MAJ%.%PHP_MIN%
    pause
    goto :end
)

:: 检查PHP扩展
call :check_php_extensions
if errorlevel 1 (
    pause
    goto :end
)

call :check_mysql

echo.
echo [步骤] 创建数据库...
if "%MYSQL_INSTALLED%"=="1" (
    mysql -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USERNAME%" -p"%DB_PASSWORD%" -e "CREATE DATABASE IF NOT EXISTS `%DB_DATABASE%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo [成功] 数据库创建完成
) else (
    echo [警告] 跳过数据库创建，请手动创建
)

echo.
echo [步骤] 配置环境变量...
if not exist "%PROJECT_DIR%backend\.env" (
    copy "%PROJECT_DIR%backend\.env.example" "%PROJECT_DIR%backend\.env" > nul
    echo [成功] .env文件已从模板创建
    echo [警告] 请编辑 backend\.env 文件，配置以下项目：
    echo         DB_PASS     - 数据库密码
    echo         JWT_SECRET   - JWT密钥(必须改为随机字符串!)
    echo         AI_DEEPSEEK_API_KEY - AI功能API Key(可选)
) else (
    echo [警告] backend\.env文件已存在，跳过
)

echo.
echo [步骤] 安装Composer依赖(PHP后端包)...
where composer >nul 2>&1
if !errorlevel! equ 0 (
    cd /d "%PROJECT_DIR%backend"
    call composer install --no-dev --optimize-autoloader
    cd /d "%PROJECT_DIR%"
    echo [成功] Composer依赖安装完成
) else (
    echo [错误] Composer未安装，请先安装Composer
    echo 下载地址: https://getcomposer.org/download/
    pause
    goto :end
)

echo.
echo [步骤] 构建前端资源(Vue 3 + Vite)...
where npm >nul 2>&1
if !errorlevel! equ 0 (
    cd /d "%PROJECT_DIR%frontend"
    call npm install
    call npm run build
    cd /d "%PROJECT_DIR%"
    
    if exist frontend\dist (
        mkdir backend\public\assets 2> nul
        xcopy /E /Y /I frontend\dist\*.* backend\public\assets\ > nul
        echo [成功] 前端构建完成，静态资源已复制到 backend\public\assets\
    ) else (
        echo [警告] frontend\dist 目录不存在，构建可能失败
    )
) else (
    echo [警告] npm未安装，跳过前端构建
    echo 请手动执行: cd frontend ^&^& npm install ^&^& npm run build
    echo 然后将 frontend\dist\ 复制到 backend\public\assets\
)

echo.
echo [步骤] 初始化数据库结构(SQL导入方式)...
if "%MYSQL_INSTALLED%"=="1" (
    if exist "%PROJECT_DIR%database\migrations\20260418_create_all_tables.sql" (
        mysql -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USERNAME%" -p"%DB_PASSWORD%" %DB_DATABASE% < "%PROJECT_DIR%database\migrations\20260418_create_all_tables.sql"
        echo [成功] 数据库表结构导入完成(17张表)
        
        set /p IMPORT_SAMPLE="是否导入示例数据? (Y/N): "
        if /i "%IMPORT_SAMPLE%"=="Y" (
            if exist "%PROJECT_DIR%database\seeds\sample_data.sql" (
                mysql -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USERNAME%" -p"%DB_PASSWORD%" %DB_DATABASE% < "%PROJECT_DIR%database\seeds\sample_data.sql"
                echo [成功] 示例数据导入完成
            ) else (
                echo [警告] 未找到示例数据文件
            )
        )
    ) else (
        echo [警告] SQL迁移文件不存在
    )
) else (
    echo [警告] MySQL客户端不可用，跳过数据库初始化
    echo 手动执行: mysql -u root -p ai_cms ^< database\migrations\20260418_create_all_tables.sql
)

echo.
echo [步骤] 设置目录权限...
mkdir backend\runtime\cache 2>nul
mkdir backend\runtime\log 2>nul
mkdir backend\runtime\temp 2>nul
echo [成功] 目录权限设置完成(Windows下通常不需要特殊权限设置)

echo.
echo ============================================
echo [成功] 原生部署准备完成!
echo ============================================ 
echo.
echo   后续操作步骤:
echo.
echo   [必须] 1. 编辑 backend\.env 配置数据库连接信息
echo           确保 DB_HOST/DB_PASS/DB_NAME 与实际环境一致
echo.
echo   [必须] 2. 启动基础服务: MySQL、Redis
echo.
echo   [选一] 3A. 开发模式启动(快速验证):
echo               cd backend ^&^& php think run --port=8080
echo               访问: http://localhost:8080
echo.
echo           3B. 生产模式(Nginx+PHP-FPM):
echo               参考配置: deploy\nginx\aicms.conf
echo               访问: http://your-server-ip
echo.
echo   默认账号: admin / Admin@2026
echo ============================================
goto :end

:::::::::::::::::::::::::::::::::::::
::  仅初始化数据库
:::::::::::::::::::::::::::::::::::::

:init_database
echo.
echo [步骤] 开始初始化数据库...
echo.

where mysql >nul 2>&1
if !errorlevel! neq 0 (
    echo [错误] MySQL客户端未安装
    pause
    goto :end
)

echo [步骤] 创建数据库...
mysql -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USERNAME%" -p"%DB_PASSWORD%" -e "CREATE DATABASE IF NOT EXISTS `%DB_DATABASE%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo [步骤] 执行迁移脚本...
mysql -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USERNAME%" -p"%DB_PASSWORD%" %DB_DATABASE% < "%PROJECT_DIR%database\migrations\20260418_create_all_tables.sql"
echo [成功] 迁移脚本执行完成

echo [步骤] 导入示例数据...
mysql -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USERNAME%" -p"%DB_PASSWORD%" %DB_DATABASE% < "%PROJECT_DIR%database\seeds\sample_data.sql"
echo [成功] 示例数据导入完成

echo.
echo ============================================
echo [成功] 数据库初始化完成!
echo ============================================
goto :end

:::::::::::::::::::::::::::::::::::::
::  显示帮助
:::::::::::::::::::::::::::::::::::::

:show_help
echo AI-CMS Windows安装脚本 v2.0.0
echo 框架: ThinkPHP 8.1 | 前端: Vue 3 + Vite
echo.
echo 用法: install.bat [选项]
echo.
echo   选项:
echo     --docker     Docker部署模式（推荐，一键搞定）
echo     --native     原生部署模式（需自行安装PHP/MySQL/Redis/Nginx）
echo     --init-db    仅初始化数据库
echo     --help       显示此帮助信息
echo.
echo   环境变量 (可通过set命令设置):
echo     DB_HOST       数据库主机 ^(默认: localhost^)
echo     DB_PORT       数据库端口 ^(默认: 3306^)
echo     DB_DATABASE   数据库名称 ^(默认: ai_cms^)
echo     DB_USERNAME   数据库用户名 ^(默认: root^)
echo     DB_PASSWORD   数据库密码 ^(默认: root123456^)
echo.
echo   示例:
echo     install.bat --docker
echo     install.bat --native
echo     install.bat --init-db
echo.
echo   注意事项:
echo     - 原生模式要求 PHP ^>= 8.0.5 ^(推荐 8.4+^)
echo     - 需要PHP扩展: pdo_mysql, redis, gd, bcmath, mbstring, xml, zip
echo     - 前端构建需要 Node.js 18+ 和 npm
echo.
goto :end

:::::::::::::::::::::::::::::::::::::
::  主函数
:::::::::::::::::::::::::::::::::::::

:main
echo ============================================
echo        AI-CMS Windows安装脚本 v2.0.0
echo      ThinkPHP 8.1 + Vue 3 + Element Plus
echo ============================================
echo.

set "ARG=%~1"

if "%ARG%"=="--docker" (
    goto :install_docker
) else if "%ARG%"=="--native" (
    goto :install_native
) else if "%ARG%"=="--init-db" (
    goto :init_database
) else if "%ARG%"=="--help" (
    goto :show_help
) else (
    echo 请指定安装模式:
    echo   --docker  Docker部署模式（推荐）
    echo   --native  原生部署模式（高级用户）
    echo   --init-db 仅初始化数据库
    echo   --help    显示帮助信息
    echo.
    echo 或直接按回车进行交互式安装...
    echo.
    set /p USE_DOCKER="是否使用Docker部署? (Y/N): "
    if /i "%USE_DOCKER%"=="Y" (
        goto :install_docker
    ) else (
        goto :install_native
    )
)

:end
echo.
pause
endlocal
