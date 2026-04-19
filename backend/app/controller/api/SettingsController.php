<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\exception\BusinessException;
use think\facade\Db;

/**
 * 系统设置控制器
 */
class SettingsController extends BaseController
{
    /**
     * 表名
     */
    protected string $table = 'configs';

    /**
     * 获取所有设置
     */
    public function index(): \think\Response
    {
        $settings = Db::name($this->table)->select()->toArray();
        
        $grouped = [];
        foreach ($settings as $setting) {
            $group = $setting['group_name'] ?? 'basic';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][$setting['key']] = $this->parseValue($setting['value'], $setting['value_type']);
        }
        
        return $this->success($grouped);
    }

    /**
     * 更新设置
     */
    public function update(): \think\Response
    {
        $input = $this->getInput();
        
        if (empty($input)) {
            throw new BusinessException('缺少设置数据', 400);
        }
        
        $updated = 0;
        
        Db::startTrans();
        try {
            foreach ($input as $group => $items) {
                if (!is_array($items)) {
                    continue;
                }
                
                foreach ($items as $key => $value) {
                    $setting = Db::name($this->table)
                        ->where('group_name', '=', $group)
                        ->where('key', '=', $key)
                        ->find();
                    
                    if ($setting) {
                        Db::name($this->table)
                            ->where('id', '=', $setting['id'])
                            ->update([
                                'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                    } else {
                        Db::name($this->table)->insert([
                            'group_name' => $group,
                            'key' => $key,
                            'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                            'value_type' => $this->detectType($value),
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                    $updated++;
                }
            }
            
            Db::commit();
            
            return $this->success(['updated' => $updated], '设置更新成功');
            
        } catch (\Exception $e) {
            Db::rollback();
            throw new BusinessException('设置更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取指定分组的设置
     */
    public function getByGroup(): \think\Response
    {
        $group = $this->request->param('group', 'basic');
        
        $settings = Db::name($this->table)
            ->where('group_name', '=', $group)
            ->select()
            ->toArray();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['key']] = $this->parseValue($setting['value'], $setting['value_type']);
        }
        
        return $this->success($result);
    }

    /**
     * 更新指定分组的设置
     */
    public function updateByGroup(): \think\Response
    {
        $group = $this->request->param('group', 'basic');
        $input = $this->getInput();

        if (empty($input)) {
            throw new BusinessException('缺少设置数据', 400);
        }

        $updated = 0;

        Db::startTrans();
        try {
            foreach ($input as $key => $value) {
                // 跳过路由参数和系统保留字段
                if (in_array($key, ['group', 'id', '_method'])) {
                    continue;
                }

                $setting = Db::name($this->table)
                    ->where('group_name', '=', $group)
                    ->where('key', '=', $key)
                    ->find();

                $type = $this->detectType($value);

                if ($setting) {
                    Db::name($this->table)
                        ->where('id', '=', $setting['id'])
                        ->update([
                            'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                            'value_type' => $type,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                } else {
                    Db::name($this->table)->insert([
                        'group_name' => $group,
                        'key' => $key,
                        'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                        'value_type' => $type,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $updated++;
            }

            Db::commit();

            // 验证是否实际有数据被处理
            if ($updated === 0) {
                throw new BusinessException('没有可更新的设置项');
            }

            return $this->success(['updated' => $updated], "成功更新 {$updated} 条设置");

        } catch (\Exception $e) {
            Db::rollback();
            throw new BusinessException('设置更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 解析值
     */
    protected function parseValue(string $value, string $type): mixed
    {
        switch ($type) {
            case 'number':
                return is_numeric($value) ? (strpos($value, '.') !== false ? (float) $value : (int) $value) : $value;
            case 'boolean':
                return in_array(strtolower($value), ['1', 'true', 'yes', 'on']);
            case 'json':
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            case 'array':
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            default:
                return $value;
        }
    }

    /**
     * 检测类型
     */
    protected function detectType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'number';
        }
        if (is_float($value)) {
            return 'number';
        }
        if (is_array($value)) {
            return 'json';
        }
        return 'string';
    }

    /**
     * 获取设置值(快捷方法)
     */
    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = Db::name('configs')
            ->where('group_name', '=', $group)
            ->where('key', '=', $key)
            ->find();
        
        if (!$setting) {
            return $default;
        }
        
        $value = $setting['value'];
        $type = $setting['value_type'];
        
        switch ($type) {
            case 'number':
                return is_numeric($value) ? (strpos($value, '.') !== false ? (float) $value : (int) $value) : $value;
            case 'boolean':
                return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on']);
            case 'json':
            case 'array':
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            default:
                return $value;
        }
    }

    /**
     * 设置值(快捷方法)
     */
    public static function set(string $group, string $key, mixed $value): bool
    {
        $setting = Db::name('configs')
            ->where('group_name', '=', $group)
            ->where('key', '=', $key)
            ->find();
        
        $type = self::detectTypeStatic($value);
        
        if ($setting) {
            return Db::name('configs')
                ->where('id', '=', $setting['id'])
                ->update([
                    'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                    'value_type' => $type,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]) !== false;
        } else {
            return Db::name('configs')->insert([
                'group_name' => $group,
                'key' => $key,
                'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                'value_type' => $type,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]) !== false;
        }
    }

    /**
     * 检测类型(静态)
     */
    protected static function detectTypeStatic(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'number';
        }
        if (is_float($value)) {
            return 'number';
        }
        if (is_array($value)) {
            return 'json';
        }
        return 'string';
    }
}
