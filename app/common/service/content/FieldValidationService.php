<?php
declare(strict_types=1);

namespace app\common\service\content;

/**
 * 字段验证规则引擎 (V2.9.36 CM-4)
 *
 * 提供20种内置验证规则、条件显示逻辑评估等
 */
class FieldValidationService
{
    /**
     * 20种内置验证规则
     */
    public const RULE_REQUIRED     = 'required';
    public const RULE_MIN_LENGTH   = 'min_length';
    public const RULE_MAX_LENGTH   = 'max_length';
    public const RULE_MIN_VALUE    = 'min_value';
    public const RULE_MAX_VALUE    = 'max_value';
    public const RULE_PATTERN      = 'pattern';
    public const RULE_EMAIL        = 'email';
    public const RULE_PHONE        = 'phone';
    public const RULE_URL          = 'url';
    public const RULE_NUMERIC      = 'numeric';
    public const RULE_INTEGER      = 'integer';
    public const RULE_FLOAT        = 'float';
    public const RULE_DATE_FORMAT  = 'date_format';
    public const RULE_REMOTE       = 'remote';
    public const RULE_UNIQUE       = 'unique';
    public const RULE_EXISTS       = 'exists';
    public const RULE_NOT_IN       = 'not_in';
    public const RULE_IN           = 'in';
    public const RULE_MULTIPLE     = 'multiple';
    public const RULE_CUSTOM       = 'custom';

    /**
     * 条件运算符
     */
    public const OP_EQUALS         = 'equals';
    public const OP_NOT_EQUALS     = 'not_equals';
    public const OP_GREATER_THAN   = 'greater_than';
    public const OP_LESS_THAN      = 'less_than';
    public const OP_CONTAINS       = 'contains';
    public const OP_NOT_CONTAINS   = 'not_contains';
    public const OP_IS_EMPTY       = 'is_empty';
    public const OP_IS_NOT_EMPTY   = 'is_not_empty';

    /**
     * 验证值，返回验证结果
     * @param mixed $value 待验证的值
     * @param array $rules 验证规则数组 [['rule'=>'required'], ['rule'=>'min_length','params'=>['value'=>3]]]
     * @return array ['valid'=>bool, 'errors'=>string[]]
     */
    public function validate($value, array $rules): array
    {
        $errors = [];

        foreach ($rules as $ruleDef) {
            $ruleName = $ruleDef['rule'] ?? '';
            $params   = $ruleDef['params'] ?? [];
            $message  = $ruleDef['message'] ?? '';

            $result = $this->testRule($ruleName, $value, $params);
            if (!$result['valid']) {
                $errors[] = $message ?: $result['error'];
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 批量验证
     * @param array $data 表单数据 ['field_name' => value]
     * @param array $fields 字段定义 [['name'=>'field_name', 'label'=>'标签', 'rules'=>[...]]]
     * @return array ['valid'=>bool, 'errors'=>array, 'data'=>array]
     */
    public function validateBatch(array $data, array $fields): array
    {
        $allErrors = [];
        $cleanData = [];

        foreach ($fields as $field) {
            $name  = $field['name'] ?? '';
            $label = $field['label'] ?? $name;
            $rules = $field['rules'] ?? [];
            $value = $data[$name] ?? null;

            $result = $this->validate($value, $rules);
            if (!$result['valid']) {
                foreach ($result['errors'] as $err) {
                    $allErrors[$name][] = $label . ': ' . $err;
                }
            }
            $cleanData[$name] = $value;
        }

        return [
            'valid'  => empty($allErrors),
            'errors' => $allErrors,
            'data'   => $cleanData,
        ];
    }

    /**
     * 返回20种内置验证规则列表
     */
    public function getRuleList(): array
    {
        return [
            ['rule' => self::RULE_REQUIRED,     'label' => '必填',       'group' => '基础',   'params' => false],
            ['rule' => self::RULE_MIN_LENGTH,   'label' => '最小长度',   'group' => '字符串', 'params' => ['value' => 'int']],
            ['rule' => self::RULE_MAX_LENGTH,   'label' => '最大长度',   'group' => '字符串', 'params' => ['value' => 'int']],
            ['rule' => self::RULE_MIN_VALUE,    'label' => '最小值',     'group' => '数值',   'params' => ['value' => 'number']],
            ['rule' => self::RULE_MAX_VALUE,    'label' => '最大值',     'group' => '数值',   'params' => ['value' => 'number']],
            ['rule' => self::RULE_PATTERN,      'label' => '正则匹配',   'group' => '字符串', 'params' => ['value' => 'string']],
            ['rule' => self::RULE_EMAIL,        'label' => '邮箱格式',   'group' => '格式',   'params' => false],
            ['rule' => self::RULE_PHONE,        'label' => '手机号',     'group' => '格式',   'params' => false],
            ['rule' => self::RULE_URL,          'label' => 'URL格式',    'group' => '格式',   'params' => false],
            ['rule' => self::RULE_NUMERIC,      'label' => '数字',       'group' => '类型',   'params' => false],
            ['rule' => self::RULE_INTEGER,      'label' => '整数',       'group' => '类型',   'params' => false],
            ['rule' => self::RULE_FLOAT,        'label' => '浮点数',     'group' => '类型',   'params' => false],
            ['rule' => self::RULE_DATE_FORMAT,  'label' => '日期格式',   'group' => '格式',   'params' => ['value' => 'string']],
            ['rule' => self::RULE_REMOTE,       'label' => '远程验证',   'group' => '高级',   'params' => ['url' => 'string']],
            ['rule' => self::RULE_UNIQUE,       'label' => '唯一性',     'group' => '高级',   'params' => ['table' => 'string', 'field' => 'string']],
            ['rule' => self::RULE_EXISTS,       'label' => '存在性',     'group' => '高级',   'params' => ['table' => 'string', 'field' => 'string']],
            ['rule' => self::RULE_NOT_IN,       'label' => '不在列表中', 'group' => '列表',   'params' => ['values' => 'array']],
            ['rule' => self::RULE_IN,           'label' => '在列表中',   'group' => '列表',   'params' => ['values' => 'array']],
            ['rule' => self::RULE_MULTIPLE,     'label' => '多选最少',   'group' => '列表',   'params' => ['min' => 'int']],
            ['rule' => self::RULE_CUSTOM,       'label' => '自定义',     'group' => '高级',   'params' => ['callback' => 'string']],
        ];
    }

    /**
     * 测试单条规则
     * @param string $rule 规则名称
     * @param mixed $value 待验证的值
     * @param array $params 规则参数
     * @return array ['valid'=>bool, 'error'=>string]
     */
    public function testRule(string $rule, $value, array $params = []): array
    {
        switch ($rule) {
            case self::RULE_REQUIRED:
                $valid = $value !== null && $value !== '' && $value !== [];
                return ['valid' => $valid, 'error' => $valid ? '' : '此字段为必填项'];

            case self::RULE_MIN_LENGTH:
                $min = (int)($params['value'] ?? 0);
                $valid = mb_strlen((string)$value) >= $min;
                return ['valid' => $valid, 'error' => $valid ? '' : "长度不能少于{$min}个字符"];

            case self::RULE_MAX_LENGTH:
                $max = (int)($params['value'] ?? 0);
                $valid = mb_strlen((string)$value) <= $max;
                return ['valid' => $valid, 'error' => $valid ? '' : "长度不能超过{$max}个字符"];

            case self::RULE_MIN_VALUE:
                $min = (float)($params['value'] ?? 0);
                $valid = is_numeric($value) && (float)$value >= $min;
                return ['valid' => $valid, 'error' => $valid ? '' : "值不能小于{$min}"];

            case self::RULE_MAX_VALUE:
                $max = (float)($params['value'] ?? 0);
                $valid = is_numeric($value) && (float)$value <= $max;
                return ['valid' => $valid, 'error' => $valid ? '' : "值不能大于{$max}"];

            case self::RULE_PATTERN:
                $pattern = $params['value'] ?? '';
                $valid = $pattern !== '' && preg_match('/' . $pattern . '/', (string)$value) === 1;
                return ['valid' => $valid, 'error' => $valid ? '' : '格式不正确'];

            case self::RULE_EMAIL:
                $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                return ['valid' => $valid, 'error' => $valid ? '' : '邮箱格式不正确'];

            case self::RULE_PHONE:
                $valid = preg_match('/^1[3-9]\d{9}$/', (string)$value) === 1;
                return ['valid' => $valid, 'error' => $valid ? '' : '手机号格式不正确'];

            case self::RULE_URL:
                $valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
                return ['valid' => $valid, 'error' => $valid ? '' : 'URL格式不正确'];

            case self::RULE_NUMERIC:
                $valid = is_numeric($value);
                return ['valid' => $valid, 'error' => $valid ? '' : '必须是数字'];

            case self::RULE_INTEGER:
                $valid = filter_var($value, FILTER_VALIDATE_INT) !== false;
                return ['valid' => $valid, 'error' => $valid ? '' : '必须是整数'];

            case self::RULE_FLOAT:
                $valid = is_float(filter_var($value, FILTER_VALIDATE_FLOAT)) || is_numeric($value);
                return ['valid' => $valid, 'error' => $valid ? '' : '必须是浮点数'];

            case self::RULE_DATE_FORMAT:
                $format = $params['value'] ?? 'Y-m-d';
                $d = \DateTime::createFromFormat($format, (string)$value);
                $valid = $d && $d->format($format) === (string)$value;
                return ['valid' => $valid, 'error' => $valid ? '' : "日期格式应为{$format}"];

            case self::RULE_REMOTE:
                // 远程验证需要前端AJAX调用，这里返回true（实际验证在前端/Controller层处理）
                return ['valid' => true, 'error' => ''];

            case self::RULE_UNIQUE:
                $table = $params['table'] ?? '';
                $field = $params['field'] ?? '';
                if (empty($table) || empty($field) || empty($value)) {
                    return ['valid' => true, 'error' => ''];
                }
                try {
                    $count = \think\facade\Db::name($table)->where($field, $value)->count();
                    $valid = $count === 0;
                    return ['valid' => $valid, 'error' => $valid ? '' : '该值已存在'];
                } catch (\Throwable $e) {
                    return ['valid' => true, 'error' => ''];
                }

            case self::RULE_EXISTS:
                $table = $params['table'] ?? '';
                $field = $params['field'] ?? '';
                if (empty($table) || empty($field) || empty($value)) {
                    return ['valid' => true, 'error' => ''];
                }
                try {
                    $count = \think\facade\Db::name($table)->where($field, $value)->count();
                    $valid = $count > 0;
                    return ['valid' => $valid, 'error' => $valid ? '' : '该值不存在'];
                } catch (\Throwable $e) {
                    return ['valid' => true, 'error' => ''];
                }

            case self::RULE_NOT_IN:
                $values = $params['values'] ?? [];
                $valid = !in_array($value, $values, true);
                return ['valid' => $valid, 'error' => $valid ? '' : '该值不被允许'];

            case self::RULE_IN:
                $values = $params['values'] ?? [];
                $valid = in_array($value, $values, true);
                return ['valid' => $valid, 'error' => $valid ? '' : '请选择有效的选项'];

            case self::RULE_MULTIPLE:
                $min = (int)($params['min'] ?? 1);
                $count = is_array($value) ? count($value) : (empty($value) ? 0 : 1);
                $valid = $count >= $min;
                return ['valid' => $valid, 'error' => $valid ? '' : "至少选择{$min}项"];

            case self::RULE_CUSTOM:
                // 自定义规则需要前端/Controller层处理
                return ['valid' => true, 'error' => ''];

            default:
                return ['valid' => true, 'error' => ''];
        }
    }

    /**
     * 评估条件显示逻辑
     * @param array $condition ['field'=>'field_name', 'operator'=>'equals', 'value'=>'target_value']
     * @param array $data 表单数据
     * @return bool
     */
    public function evaluateCondition(array $condition, array $data): bool
    {
        $field    = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '';
        $target   = $condition['value'] ?? null;

        $actual = $data[$field] ?? null;

        switch ($operator) {
            case self::OP_EQUALS:
                return (string)$actual === (string)$target;

            case self::OP_NOT_EQUALS:
                return (string)$actual !== (string)$target;

            case self::OP_GREATER_THAN:
                return is_numeric($actual) && is_numeric($target) && (float)$actual > (float)$target;

            case self::OP_LESS_THAN:
                return is_numeric($actual) && is_numeric($target) && (float)$actual < (float)$target;

            case self::OP_CONTAINS:
                if (is_array($actual)) {
                    return in_array($target, $actual, true);
                }
                return mb_strpos((string)$actual, (string)$target) !== false;

            case self::OP_NOT_CONTAINS:
                if (is_array($actual)) {
                    return !in_array($target, $actual, true);
                }
                return mb_strpos((string)$actual, (string)$target) === false;

            case self::OP_IS_EMPTY:
                if (is_array($actual)) {
                    return empty($actual);
                }
                return $actual === null || $actual === '';

            case self::OP_IS_NOT_EMPTY:
                if (is_array($actual)) {
                    return !empty($actual);
                }
                return $actual !== null && $actual !== '';

            default:
                return true;
        }
    }

    /**
     * 评估多个条件（支持AND/OR组合）
     * @param array $conditions ['logic'=>'AND', 'items'=>[condition1, condition2, ...]]
     * @return bool
     */
    public function evaluateConditions(array $conditions, array $data): bool
    {
        $logic = strtoupper($conditions['logic'] ?? 'AND');
        $items = $conditions['items'] ?? [];

        if (empty($items)) {
            return true;
        }

        // 支持嵌套条件组
        $results = [];
        foreach ($items as $item) {
            if (isset($item['items']) && is_array($item['items'])) {
                // 递归评估嵌套条件组
                $results[] = $this->evaluateConditions($item, $data);
            } else {
                $results[] = $this->evaluateCondition($item, $data);
            }
        }

        if ($logic === 'OR') {
            return in_array(true, $results, true);
        }

        // AND
        return !in_array(false, $results, true);
    }
}
