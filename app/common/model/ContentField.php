<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Cache;

/**
 * 内容模型字段 — V2.9.36 CM-1
 */
class ContentField extends Model
{
    protected $name = 'content_field';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';

    protected $json = ['field_options', 'field_validation', 'field_layout'];
    protected $jsonAssoc = true;

    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_EDITOR = 'editor';
    public const TYPE_NUMBER = 'number';
    public const TYPE_SELECT = 'select';
    public const TYPE_MULTI_SELECT = 'multi-select';
    public const TYPE_RADIO = 'radio';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_COLOR = 'color';
    public const TYPE_IMAGE = 'image';
    public const TYPE_IMAGES = 'images';
    public const TYPE_FILE = 'file';
    public const TYPE_FILES = 'files';
    public const TYPE_LINK = 'link';
    public const TYPE_GROUP = 'group';
    public const TYPE_REPEATER = 'repeater';
    public const TYPE_SWITCH = 'switch';
    public const TYPE_LOCATION = 'location';
    public const TYPE_RATING = 'rating';
    public const TYPE_ICON = 'icon';
    public const TYPE_URL = 'url';
    public const TYPE_EMAIL = 'email';
    public const TYPE_PHONE = 'phone';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_HIDDEN = 'hidden';
    public const TYPE_NOTICE = 'notice';

    public const FIELD_TYPE_MAP = [
        self::TYPE_TEXT         => ['input' => 'text', 'db_type' => 'string'],
        self::TYPE_TEXTAREA     => ['input' => 'textarea', 'db_type' => 'text'],
        self::TYPE_EDITOR       => ['input' => 'rich_text', 'db_type' => 'text'],
        self::TYPE_NUMBER       => ['input' => 'number', 'db_type' => 'float'],
        self::TYPE_SELECT       => ['input' => 'select', 'db_type' => 'string'],
        self::TYPE_MULTI_SELECT => ['input' => 'multi_select', 'db_type' => 'json'],
        self::TYPE_RADIO        => ['input' => 'radio', 'db_type' => 'string'],
        self::TYPE_CHECKBOX     => ['input' => 'checkbox', 'db_type' => 'json'],
        self::TYPE_DATE         => ['input' => 'date', 'db_type' => 'date'],
        self::TYPE_DATETIME     => ['input' => 'datetime', 'db_type' => 'datetime'],
        self::TYPE_COLOR        => ['input' => 'color', 'db_type' => 'string'],
        self::TYPE_IMAGE        => ['input' => 'image_upload', 'db_type' => 'string'],
        self::TYPE_IMAGES       => ['input' => 'multi_image_upload', 'db_type' => 'json'],
        self::TYPE_FILE         => ['input' => 'file_upload', 'db_type' => 'string'],
        self::TYPE_FILES        => ['input' => 'multi_file_upload', 'db_type' => 'json'],
        self::TYPE_LINK         => ['input' => 'content_link', 'db_type' => 'json'],
        self::TYPE_GROUP        => ['input' => 'group', 'db_type' => null],
        self::TYPE_REPEATER     => ['input' => 'repeater', 'db_type' => 'json'],
        self::TYPE_SWITCH       => ['input' => 'switch', 'db_type' => 'int'],
        self::TYPE_LOCATION     => ['input' => 'map_location', 'db_type' => 'json'],
        self::TYPE_RATING       => ['input' => 'star_rating', 'db_type' => 'int'],
        self::TYPE_ICON         => ['input' => 'icon_picker', 'db_type' => 'string'],
        self::TYPE_URL          => ['input' => 'url', 'db_type' => 'string'],
        self::TYPE_EMAIL        => ['input' => 'email', 'db_type' => 'string'],
        self::TYPE_PHONE        => ['input' => 'tel', 'db_type' => 'string'],
        self::TYPE_PASSWORD     => ['input' => 'password', 'db_type' => 'string'],
        self::TYPE_HIDDEN       => ['input' => 'hidden', 'db_type' => 'string'],
        self::TYPE_NOTICE       => ['input' => 'notice', 'db_type' => null],
    ];

    /**
     * 获取模型所有字段（带缓存）
     */
    public static function getModelFields(int $modelId): array
    {
        return Cache::remember(
            'content_fields_' . $modelId,
            function () use ($modelId) {
                return self::where('model_id', $modelId)
                    ->where('status', 1)
                    ->order('sort_order', 'asc')
                    ->select()
                    ->toArray();
            },
            300
        );
    }

    /**
     * 获取所有字段类型列表
     */
    public static function getFieldTypeList(): array
    {
        return [
            ['type' => self::TYPE_TEXT, 'label' => '单行文本', 'group' => '基础'],
            ['type' => self::TYPE_TEXTAREA, 'label' => '多行文本', 'group' => '基础'],
            ['type' => self::TYPE_EDITOR, 'label' => '富文本编辑器', 'group' => '基础'],
            ['type' => self::TYPE_NUMBER, 'label' => '数字输入', 'group' => '基础'],
            ['type' => self::TYPE_SELECT, 'label' => '单选下拉', 'group' => '选择'],
            ['type' => self::TYPE_MULTI_SELECT, 'label' => '多选下拉', 'group' => '选择'],
            ['type' => self::TYPE_RADIO, 'label' => '单选框', 'group' => '选择'],
            ['type' => self::TYPE_CHECKBOX, 'label' => '多选框', 'group' => '选择'],
            ['type' => self::TYPE_DATE, 'label' => '日期选择', 'group' => '时间'],
            ['type' => self::TYPE_DATETIME, 'label' => '日期时间', 'group' => '时间'],
            ['type' => self::TYPE_COLOR, 'label' => '颜色选择', 'group' => '特殊'],
            ['type' => self::TYPE_IMAGE, 'label' => '单张图片', 'group' => '媒体'],
            ['type' => self::TYPE_IMAGES, 'label' => '多张图片', 'group' => '媒体'],
            ['type' => self::TYPE_FILE, 'label' => '文件上传', 'group' => '媒体'],
            ['type' => self::TYPE_FILES, 'label' => '多文件上传', 'group' => '媒体'],
            ['type' => self::TYPE_LINK, 'label' => '关联内容', 'group' => '关联'],
            ['type' => self::TYPE_GROUP, 'label' => '字段分组', 'group' => '布局'],
            ['type' => self::TYPE_REPEATER, 'label' => '重复字段组', 'group' => '布局'],
            ['type' => self::TYPE_SWITCH, 'label' => '开关', 'group' => '基础'],
            ['type' => self::TYPE_LOCATION, 'label' => '地图位置', 'group' => '特殊'],
            ['type' => self::TYPE_RATING, 'label' => '星级评分', 'group' => '特殊'],
            ['type' => self::TYPE_ICON, 'label' => '图标选择', 'group' => '特殊'],
            ['type' => self::TYPE_URL, 'label' => 'URL链接', 'group' => '基础'],
            ['type' => self::TYPE_EMAIL, 'label' => '邮箱', 'group' => '基础'],
            ['type' => self::TYPE_PHONE, 'label' => '电话', 'group' => '基础'],
            ['type' => self::TYPE_PASSWORD, 'label' => '密码', 'group' => '基础'],
            ['type' => self::TYPE_HIDDEN, 'label' => '隐藏字段', 'group' => '布局'],
            ['type' => self::TYPE_NOTICE, 'label' => '提示信息', 'group' => '布局'],
        ];
    }
}
