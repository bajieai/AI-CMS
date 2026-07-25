<?php

declare(strict_types=1);

namespace app\common\service;

/**
 * V2.9.35 PLUG-5: 插件验证服务
 */
class PluginValidateService
{
    /**
     * 验证插件结构
     */
    public function validate(string $identifier): array
    {
        $pluginPath = root_path() . 'plugin' . DIRECTORY_SEPARATOR . $identifier . DIRECTORY_SEPARATOR;
        $errors = [];

        // 1. 检查plugin.php
        $pluginFile = $pluginPath . 'plugin.php';
        if (!file_exists($pluginFile)) {
            $errors[] = '缺少必须文件 plugin.php';
        } else {
            $config = include $pluginFile;
            if (!is_array($config)) {
                $errors[] = 'plugin.php 必须返回数组';
            } else {
                if (empty($config['identifier'])) $errors[] = 'plugin.php 缺少 identifier 字段';
                if (empty($config['name'])) $errors[] = 'plugin.php 缺少 name 字段';
                if (empty($config['version'])) $errors[] = 'plugin.php 缺少 version 字段';
                if (!empty($config['identifier']) && $config['identifier'] !== $identifier) {
                    $errors[] = 'identifier 与目录名不一致';
                }
            }
        }

        // 2. 检查config.php
        $configFile = $pluginPath . 'config.php';
        if (!file_exists($configFile)) {
            $errors[] = '缺少必须文件 config.php';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }
}
