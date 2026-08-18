<?php

declare(strict_types=1);

namespace app\common\support;

/**
 * 系统预置内容模型的唯一编号定义。
 * 模型 ID、content_model.type、cate.type、content.type 保持一致。
 */
final class ContentTypeMap
{
    public const INFO = 1;
    public const PAGE = 2;
    public const PRODUCT = 3;
    public const CASE = 4;
    public const DOWNLOAD = 5;
    public const JOB = 6;
    public const GALLERY = 7;
    public const VIDEO = 8;

    /** @return array<int, string> */
    public static function slugByType(): array
    {
        return [
            self::INFO => 'info',
            self::PAGE => 'page',
            self::PRODUCT => 'product',
            self::CASE => 'case',
            self::DOWNLOAD => 'download',
            self::JOB => 'job',
            self::GALLERY => 'gallery',
            self::VIDEO => 'video',
        ];
    }

    /** @return array<string, int> */
    public static function typeBySlug(): array
    {
        return array_flip(self::slugByType());
    }

    /** @return array<int, string> */
    public static function nameByType(): array
    {
        return [
            self::INFO => '信息资讯',
            self::PAGE => '单页介绍',
            self::PRODUCT => '产品信息',
            self::CASE => '企业案例',
            self::DOWNLOAD => '软件下载',
            self::JOB => '人才招聘',
            self::GALLERY => '图片图集',
            self::VIDEO => '视频内容',
        ];
    }

    /** @return array<int, string> */
    public static function categoryNameByType(): array
    {
        return [
            self::INFO => '信息',
            self::PAGE => '单页',
            self::PRODUCT => '产品',
            self::CASE => '案例',
            self::DOWNLOAD => '下载',
            self::JOB => '招聘',
            self::GALLERY => '图集',
            self::VIDEO => '视频',
        ];
    }

    /**
     * 内容类型对应的 Bootstrap 徽章颜色 class（后台分类/内容列表“类型”标识）
     * @return array<int, string>
     */
    public static function badgeColorByType(): array
    {
        return [
            self::INFO     => 'bg-success',
            self::PAGE     => 'bg-dark',
            self::PRODUCT  => 'bg-primary',
            self::CASE     => 'bg-info',
            self::DOWNLOAD => 'bg-warning text-dark',
            self::JOB      => 'bg-secondary',
            self::GALLERY  => 'bg-danger',
            self::VIDEO    => 'bg-info',
        ];
    }

    public static function pageType(): int
    {
        return self::PAGE;
    }
}
