<?php
declare(strict_types=1);

namespace app;

use think\App;
use think\Request;
use think\Response;
use app\exception\BusinessException;

/**
 * 基础控制器
 */
abstract class BaseController
{
    /**
     * Request实例
     */
    protected Request $request;

    /**
     * 应用实例
     */
    protected App $app;

    /**
     * 是否批量操作
     */
    protected bool $batchOperation = false;

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        
        // 初始化
        $this->initialize();
    }

    /**
     * 初始化
     */
    protected function initialize(): void
    {
    }

    /**
     * 返回成功JSON响应
     */
    protected function success(mixed $data = null, string $message = '操作成功', int $code = 200): Response
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 返回错误JSON响应
     */
    protected function error(string $message = '操作失败', int $code = 400, mixed $data = null): Response
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 返回分页JSON响应
     */
    protected function paginate(\think\Collection|array $data, int $total, int $page, int $perPage): Response
    {
        return json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'list' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * 获取分页参数
     */
    protected function getPageParams(): array
    {
        $page = max(1, (int) $this->request->param('page', 1));
        $perPage = min(100, max(1, (int) $this->request->param('per_page', 15)));
        
        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    /**
     * 获取排序参数
     */
    protected function getSortParams(): array
    {
        $orderField = $this->request->param('order_field', 'id');
        $orderType = $this->request->param('order_type', 'desc');
        
        // 安全处理字段名
        $orderField = preg_replace('/[^a-zA-Z0-9_]/', '', $orderField);
        $orderType = in_array(strtolower($orderType), ['asc', 'desc']) ? strtolower($orderType) : 'desc';
        
        return [$orderField => $orderType];
    }

    /**
     * 获取筛选参数
     */
    protected function getFilterParams(array $allowedFields = []): array
    {
        $filters = [];
        $params = $this->request->param();
        
        foreach ($params as $key => $value) {
            if (str_starts_with($key, 'filter_') && $value !== '' && $value !== null) {
                $field = substr($key, 7);
                if (empty($allowedFields) || in_array($field, $allowedFields)) {
                    $filters[$field] = $value;
                }
            }
        }
        
        return $filters;
    }

    /**
     * 获取搜索参数
     */
    protected function getSearchParams(): ?string
    {
        $keyword = $this->request->param('keyword', '');
        return !empty($keyword) ? trim($keyword) : null;
    }

    /**
     * 获取请求数据
     * 支持 GET/POST/PUT/DELETE 以及 JSON/form-data 请求体
     */
    protected function getInput(): array
    {
        $input = $this->request->post();

        if (!empty($input)) {
            return $input;
        }

        // 尝试获取原始输入数据（用于 PUT/PATCH/DELETE + JSON 请求）
        $contentType = $this->request->header('Content-Type', '');

        if (str_contains($contentType, 'application/json')) {
            $rawContent = $this->request->getInput();
            if (!empty($rawContent)) {
                $decoded = json_decode($rawContent, true);
                if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
        }

        // 尝试获取所有参数（包含 query params 和 body params）
        $params = $this->request->param();
        if (!empty($params) && is_array($params)) {
            // 过滤掉路由参数（如 group, id 等）
            return array_filter($params, function ($key) {
                return !in_array($key, ['group', 'id', 'page', 'per_page']);
            }, ARRAY_FILTER_USE_KEY);
        }

        return [];
    }

    /**
     * 获取ID参数
     */
    protected function getIdParam(): ?int
    {
        $id = $this->request->param('id');
        return $id ? (int) $id : null;
    }

    /**
     * 验证必需参数
     */
    protected function validateRequired(array $fields, array $data = []): void
    {
        $data = $data ?: $this->getInput();
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[$field] = "{$field}不能为空";
            }
        }
        
        if (!empty($errors)) {
            throw new BusinessException('参数验证失败', 422, $errors);
        }
    }

    /**
     * 验证数据并返回错误
     */
    protected function validateData(array $rules, array $data = []): array
    {
        $data = $data ?: $this->getInput();
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleList = explode('|', $rule);
            
            foreach ($ruleList as $r) {
                if ($r === 'require' && (empty($value) && $value !== '0')) {
                    $errors[$field] = "{$field}不能为空";
                    break;
                }
                
                if ($r === 'number' && $value !== null && !is_numeric($value)) {
                    $errors[$field] = "{$field}必须是数字";
                    break;
                }
                
                if ($r === 'integer' && $value !== null && !is_int($value)) {
                    $errors[$field] = "{$field}必须是整数";
                    break;
                }
                
                if ($r === 'positive' && $value !== null && (!is_numeric($value) || $value <= 0)) {
                    $errors[$field] = "{$field}必须是正数";
                    break;
                }
                
                if (str_starts_with($r, 'max:') && $value !== null) {
                    $max = (int) substr($r, 4);
                    if (is_string($value) && mb_strlen($value) > $max) {
                        $errors[$field] = "{$field}最大长度为{$max}个字符";
                        break;
                    }
                    if (is_numeric($value) && $value > $max) {
                        $errors[$field] = "{$field}最大值为{$max}";
                        break;
                    }
                }
                
                if (str_starts_with($r, 'min:') && $value !== null) {
                    $min = (int) substr($r, 4);
                    if (is_string($value) && mb_strlen($value) < $min) {
                        $errors[$field] = "{$field}最小长度为{$min}个字符";
                        break;
                    }
                    if (is_numeric($value) && $value < $min) {
                        $errors[$field] = "{$field}最小值为{$min}";
                        break;
                    }
                }
                
                if ($r === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "{$field}格式不正确";
                    break;
                }
                
                if ($r === 'phone' && $value && !preg_match('/^1[3-9]\d{9}$/', $value)) {
                    $errors[$field] = "{$field}格式不正确";
                    break;
                }
                
                if ($r === 'url' && $value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[$field] = "{$field}格式不正确";
                    break;
                }
                
                if ($r === 'array' && $value !== null && !is_array($value)) {
                    $errors[$field] = "{$field}必须是数组";
                    break;
                }
            }
        }
        
        if (!empty($errors)) {
            throw new BusinessException('数据验证失败', 422, $errors);
        }
        
        return $data;
    }

    /**
     * 空操作响应
     */
    protected function emptyOperation(): Response
    {
        return $this->error('操作不存在');
    }
}
