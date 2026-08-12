<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\ContentModel;
use app\common\model\ContentModelField;
use think\App;

/**
 * 内容模型字段管理
 */
class ContentFieldController extends AdminBaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function index(int $id = 0)
    {
        $id = $id ?: (int) $this->request->get('id', 0);
        $model = ContentModel::find($id);
        if (!$model) {
            return $this->error('内容模型不存在');
        }

        $types = [];
        foreach (ContentModelField::$typeMap as $value => $label) {
            $types[] = ['value' => $value, 'label' => $label];
        }

        return $this->view('content_model/field', [
            'model' => $model,
            'model_id' => $id,
            'fields' => ContentModelField::where('model_id', $id)->order('sort', 'asc')->select()->toArray(),
            'types' => $types,
        ]);
    }

    public function save()
    {
        $data = $this->normalizeData($this->request->post());
        if ($data['model_id'] <= 0 || !$this->fieldIsValid($data)) {
            return $this->error('字段标识和字段标签不能为空');
        }
        if (ContentModelField::where('model_id', $data['model_id'])->where('name', $data['name'])->find()) {
            return $this->error('该模型内的字段标识已存在');
        }

        $data['sort'] = (int) ContentModelField::where('model_id', $data['model_id'])->max('sort') + 10;
        ContentModelField::create($data);
        $this->clearFieldCache($data['model_id']);
        return $this->success('字段创建成功');
    }

    public function update(int $id)
    {
        $field = ContentModelField::find($id);
        if (!$field) {
            return $this->error('字段不存在');
        }

        $data = $this->normalizeData($this->request->post(), false);
        if (!$this->fieldIsValid($data)) {
            return $this->error('字段标识和字段标签不能为空');
        }
        $duplicate = ContentModelField::where('model_id', $field->model_id)->where('name', $data['name'])->where('id', '<>', $id)->find();
        if ($duplicate) {
            return $this->error('该模型内的字段标识已存在');
        }

        $field->save($data);
        $this->clearFieldCache((int) $field->model_id);
        return $this->success('字段更新成功');
    }

    public function delete(int $id)
    {
        $field = ContentModelField::find($id);
        if (!$field) {
            return $this->error('字段不存在');
        }
        $modelId = (int) $field->model_id;
        $field->delete();
        $this->clearFieldCache($modelId);
        return $this->success('字段已删除');
    }

    public function sort()
    {
        $sortData = json_decode((string) $this->request->post('sort_data', '[]'), true) ?: [];
        foreach ($sortData as $item) {
            if (!empty($item['id'])) {
                ContentModelField::where('id', (int) $item['id'])->update(['sort' => (int) ($item['sort_order'] ?? 0)]);
            }
        }
        $this->clearFieldCache((int) $this->request->post('model_id', 0));
        return $this->success('排序保存成功');
    }

    private function normalizeData(array $input, bool $includeModel = true): array
    {
        $options = trim((string) ($input['field_options'] ?? ''));
        $type = (string) ($input['field_type'] ?? 'text');
        if ($options !== '' && in_array($type, ['select', 'radio', 'checkbox'], true)) {
            $options = $this->normalizeOptions($options);
        } elseif (!in_array($type, ['select', 'radio', 'checkbox'], true)) {
            $options = '';
        }
        return array_filter([
            'model_id' => $includeModel ? (int) ($input['model_id'] ?? 0) : null,
            'name' => trim((string) ($input['field_name'] ?? '')),
            'label' => trim((string) ($input['field_label'] ?? '')),
            'type' => $type,
            'options' => $options === '' ? null : $options,
            'default_value' => (string) ($input['default_value'] ?? ''),
            'placeholder' => (string) ($input['placeholder'] ?? ''),
            'help_text' => (string) ($input['help_text'] ?? ''),
            'is_searchable' => (int) ($input['is_searchable'] ?? 0),
            'is_list_show' => (int) ($input['is_list_show'] ?? 0),
            'required' => (int) ($input['is_required'] ?? 0),
            'status' => 1,
        ], static fn ($value) => $value !== null);
    }

    private function fieldIsValid(array $data): bool
    {
        return !empty($data['name']) && !empty($data['label']);
    }

    private function normalizeOptions(string $options): string
    {
        $decoded = json_decode($options, true);
        if (is_array($decoded)) {
            $normalized = [];
            foreach ($decoded as $key => $item) {
                if (is_array($item)) {
                    $value = trim((string) ($item['value'] ?? ($item['key'] ?? '')));
                    $label = trim((string) ($item['label'] ?? ($item['value'] ?? '')));
                    if ($value === '' && $label === '') {
                        continue;
                    }
                    if ($label === '') {
                        $label = $value;
                    }
                    $normalized[] = ['value' => $value === '' ? $label : $value, 'label' => $label];
                } else {
                    $value = trim((string) $key);
                    $label = trim((string) $item);
                    if ($label === '') {
                        continue;
                    }
                    $normalized[] = ['value' => is_int($key) ? $label : $value, 'label' => $label];
                }
            }
            if ($normalized !== []) {
                return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            return '';
        }

        // 兼容旧版「值:标签」或逗号/换行分隔的纯文本
        $lines = preg_split('/[\r\n]+/', $options);
        $normalized = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (strpos($line, ':') !== false) {
                [$value, $label] = array_map('trim', explode(':', $line, 2));
                $normalized[] = ['value' => $value, 'label' => $label !== '' ? $label : $value];
            } else {
                $normalized[] = ['value' => $line, 'label' => $line];
            }
        }
        return $normalized !== [] ? json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
    }

    private function clearFieldCache(int $modelId): void
    {
        if ($modelId > 0) {
            \think\facade\Cache::delete('content_model_fields_' . $modelId);
        }
    }
}
