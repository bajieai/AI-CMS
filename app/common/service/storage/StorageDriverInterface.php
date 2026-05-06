<?php
declare(strict_types=1);

namespace app\common\service\storage;

/**
 * 存储驱动接口 - V2.6
 * 统一本地存储、阿里云OSS、腾讯云COS等对象存储的操作
 */
interface StorageDriverInterface
{
    /**
     * 上传文件
     * @param string $localPath 本地临时文件路径
     * @param string $savePath 存储目标路径（如 uploads/2026/05/image.jpg）
     * @param array $options 额外选项
     * @return array ['success' => bool, 'url' => string, 'path' => string, 'error' => string]
     */
    public function upload(string $localPath, string $savePath, array $options = []): array;

    /**
     * 删除文件
     * @param string $path 存储路径
     * @return bool
     */
    public function delete(string $path): bool;

    /**
     * 获取文件访问URL
     * @param string $path 存储路径
     * @return string
     */
    public function getUrl(string $path): string;

    /**
     * 检查文件是否存在
     * @param string $path 存储路径
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * 获取驱动名称
     */
    public function getName(): string;

    /**
     * 获取驱动显示名称
     */
    public function getDisplayName(): string;

    /**
     * 获取配置表单字段定义
     * @return array 字段定义数组 [['name' => 'key', 'label' => '显示名', 'type' => 'text', 'required' => true], ...]
     */
    public function getConfigFields(): array;

    /**
     * 验证配置是否有效
     * @param array $config
     * @return bool
     */
    public function validateConfig(array $config): bool;
}
