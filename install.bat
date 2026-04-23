@echo off
chcp 65001 > nul
setlocal enabledelayedexpansion

::: AI-CMS V2.0 Windows安装脚本
::: 框架: ThinkPHP 8.1 (多应用模式)
::: 使用方式: install.bat [--docker|--native|--init-db]

set "SCRIPT_DIR=%~dp0"
set "PROJECT_DIR=%SCRIPT_DIR%"

::: 配置变量
set "DB_HOST=localhost"
set "DB_PORT=3306"
set "DB_DATABASE=aicms_v2"
set "DB_USERNAME=root"
set "DB_PASSWORD=root123456"

::: 颜色支持 (Windows 10+)
reg query "HKCU\Console" /v VirtualTerminalLevel 2>nul >nul || (
    reg add "HKCU\Console" /v VirtualTerminalLevel /t REG_DWORD /d 1 /f >nul 2>&1
)

::: 主入口
goto :main

::::::::::::::::::::::::::::::::::::::
:::  检查函数
::::::::::::::::::::::::::::::::::::::

::check_docker
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

::check_php
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

::check_php_extensions
echo [步骤] 检查PHP必需扩展...
set "MISSING_EXTS="

for %%E in (pdo_mysql gd bcmath mbstring xml zip json) do (
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

::check_mysql
where mysql >nul 2>&1
if !errorlevel! equ 0 (
    set "MYSQL_INSTALLED=1"
    echo [成功] 检测到MySQL客户端
) else (
    set "MYSQL_INSTALLED=0"
    echo [警告] 未检测到MySQL客户端
)
goto :eof

::::::::::::::::::::::::::::::::::::::
:::  Docker部署模式（推荐）
::::::::::::::::::::::::::::::::::::::

::install_docker
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
timeout /t 15 /nobreak > nul

echo [步骤] 安装Composer依赖...
docker exec -i aicms_php composer install --no-dev --optimize-autoloader

echo.
echo ============================================
echo [成功] Docker部署完成!
echo ============================================
echo.
echo   后续操作:
echo   1. 访问 http://localhost:3000/install 进入安装向导
echo   2. 按向导完成数据库初始化和管理员创建
echo   3. 安装完成后访问:
echo      前台: http://localhost:3000
echo      后台: http://localhost:3000/admin
echo      默认账号: 在安装向导中设置
echo ============================================
goto :end

::::::::::::::::::::::::::::::::::::::
:::  原生部署模式（高级用户）
::::::::::::::::::::::::::::::::::::::

::install_native
echo.
echo [步骤] 开始原生部署模式...
echo.

call :check_php
if "%PHP_INSTALLED%"=="0" (
    echo [错误] PHP未安装，请先安装PHP 8.2+
    pause
    goto :end
)

for /f "delims=" %%v in ('php -r "echo PHP_MAJOR_VERSION;"') do set "PHP_MAJ=%%v"
for /f "delims=" %%v in ('php -r "echo PHP_MINOR_VERSION;"') do set "PHP_MIN=%%v"
if %PHP_MAJ% LSS 8 (
    echo [错误] ThinkPHP 8.1 需要 PHP ^>= 8.0.5, 当前版本: %PHP_MAJ%.%PHP_MIN%
    pause
    goto :end
)

call :check_php_extensions
if errorlevel 1 (
    pause
    goto :end
)

call :check_mysql

echo.
echo [步骤] 配置环境变量...
if not exist "%PROJECT_DIR%.env" (
    echo [警告] .env文件已存在，请确认配置正确
) else (
    echo [成功] .env文件已存在，请确认数据库配置
)

echo.
echo [步骤] 安装Composer依赖...
where composer >nul 2>&1
if !errorlevel! equ 0 (
    cd /d "%PROJECT_DIR%"
    call composer install --no-dev --optimize-autoloader
    echo [成功] Composer依赖安装完成
) else (
    echo [错误] Composer未安装，请先安装Composer
    echo 下载地址: https://getcomposer.org/download/
    pause
    goto :end
)

echo.
echo [步骤] 创建数据库...
if "%MYSQL_INSTALLED%"=="1" (
    mysql -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USERNAME%" -p"%DB_PASSWORD%" -e "CREATE DATABASE IF NOT EXISTS `%DB_DATABASE%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
    echo [成功] 数据库创建完成（如已存在则跳过）
) else (
    echo [警告] 请手动创建数据库: %DB_DATABASE%
)

echo.
echo [步骤] 设置运行时目录...
mkdir runtime\cache 2>nul
mkdir runtime\log 2>nul
mkdir runtime\temp 2>nul
mkdir public\uploads 2>nul
echo [成功] 运行时目录已创建

echo.
echo ============================================
echo [成功] 原生部署准备完成!
echo ============================================
echo.
echo   后续操作步骤:
echo.
echo   [必须] 1. 编辑 .env 配置数据库连接信息
echo           确保 DATABASE_HOSTNAME/DATABASE_PASSWORD/DATABASE_DATABASE 正确
echo.
echo   [必须] 2. 访问安装向导完成数据库初始化:
echo           php think run --port=8080
echo           然后浏览器打开 http://localhost:8080/install
echo.
echo   [或者] 3. 生产模式(Nginx+PHP-FPM):
echo           参考配置: deploy\nginx\aicms.conf
echo           访问: http://your-server-ip/install
echo ============================================
goto :end

::::::::::::::::::::::::::::::::::::::
:::  仅初始化数据库
::::::::::::::::::::::::::::::::::::::

::init_database
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

echo [步骤] 执行建表脚本...
mysql -h "%DB_HOST%" -P "%DB_PORT%" -u "%DB_USERNAME%" -p"%DB_PASSWORD%" %DB_DATABASE% < "%PROJECT_DIR%database\migrations\install.sql"
echo [成功] 数据库初始化完成（8张表+默认数据）

echo.
echo ============================================
echo [成功] 数据库初始化完成!
echo 默认管理员: admin / admin123
echo ============================================
goto :end

::::::::::::::::::::::::::::::::::::::
:::  显示帮助
::::::::::::::::::::::::::::::::::::::

::show_help
echo AI-CMS V2.0 Windows安装脚本
echo 框架: ThinkPHP 8.1 (多应用模式)
echo.
echo 用法: install.bat [选项]
echo.
echo   选项:
echo     --docker     Docker部署模式（推荐）
echo     --native     原生部署模式（需自行安装PHP/MySQL/Nginx）
echo     --init-db    仅初始化数据库
echo     --help       显示此帮助信息
echo.
echo   注意事项:
echo     - 原生模式要求 PHP ^>= 8.0.5 (推荐 8.2+)
echo     - 需要PHP扩展: pdo_mysql, gd, bcmath, mbstring, xml, zip
echo     - Docker模式会自动启动所有服务并通过Web安装向导初始化
echo     - 原生模式需手动访问 /install 完成安装向导
echo.
goto :end

::::::::::::::::::::::::::::::::::::::
:::  主函数
::::::::::::::::::::::::::::::::::::::

::main
echo ============================================
echo        AI-CMS V2.0 Windows安装脚本
echo      ThinkPHP 8.1 多应用模式
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

::end
echo.
pause
endlocal
