<?php
declare(strict_types=1);

namespace app\common\service\content;

use think\facade\Db;
use think\facade\Cache;

/**
 * 表单设计器Service — V2.9.36 CM-5
 */
class FormDesignerService
{
    public function getLayoutContainers(): array
    {
        return [
            ['type' => 'row', 'label' => '行', 'icon' => 'bi bi-layout-text-window'],
            ['type' => 'column', 'label' => '列', 'icon' => 'bi bi-layout-three-columns'],
            ['type' => 'tab', 'label' => '选项卡', 'icon' => 'bi bi-folder-tab'],
            ['type' => 'collapse', 'label' => '折叠面板', 'icon' => 'bi bi-arrows-collapse'],
            ['type' => 'card', 'label' => '卡片', 'icon' => 'bi bi-card-text'],
            ['type' => 'step', 'label' => '分步', 'icon' => 'bi bi-list-ol'],
            ['type' => 'group', 'label' => '分组', 'icon' => 'bi bi-collection'],
            ['type' => 'divider', 'label' => '分割线', 'icon' => 'bi bi-hr'],
            ['type' => 'note', 'label' => '说明文字', 'icon' => 'bi bi-info-circle'],
            ['type' => 'html', 'label' => '自定义HTML', 'icon' => 'bi bi-code-slash'],
        ];
    }

    public function saveLayout(int $modelId, array $layout): array
    {
        try {
            $model = Db::name('content_model')->where('id', $modelId)->find();
            if (!$model) return ['code' => 1, 'msg' => '模型不存在'];
            $config = json_decode($model['model_config'] ?? '{}', true) ?: [];
            $config['form_layout'] = $layout;
            Db::name('content_model')->where('id', $modelId)->update([
                'model_config' => json_encode($config, JSON_UNESCAPED_UNICODE),
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            Cache::clear();
            return ['code' => 0, 'msg' => '保存成功'];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '保存失败: ' . $e->getMessage()];
        }
    }

    public function getLayout(int $modelId): array
    {
        $model = Db::name('content_model')->where('id', $modelId)->find();
        if (!$model) return [];
        $config = json_decode($model['model_config'] ?? '{}', true) ?: [];
        return $config['form_layout'] ?? [];
    }

    public function renderForm(int $modelId, $contentId = null): string
    {
        $layout = $this->getLayout($modelId);
        $fields = \app\common\model\ContentField::getModelFields($modelId);
        $fieldMap = array_column($fields, null, 'id');
        $customFields = [];
        if ($contentId) {
            $content = Db::name('content')->where('id', $contentId)->find();
            $customFields = json_decode($content['custom_fields'] ?? '{}', true) ?: [];
        }
        $html = '<div class="form-designer-render">';
        foreach ($layout as $item) {
            $html .= $this->renderLayoutItem($item, $fieldMap, $customFields);
        }
        $html .= '</div>';
        return $html;
    }

    private function renderLayoutItem(array $item, array $fieldMap, array $customFields): string
    {
        $type = $item['type'] ?? 'row';
        $html = '';
        switch ($type) {
            case 'tab':
                $html .= '<ul class="nav nav-tabs">';
                foreach ($item['children'] ?? [] as $i => $tab) {
                    $active = $i === 0 ? 'active' : '';
                    $html .= '<li class="nav-item"><a class="nav-link ' . $active . '" data-bs-toggle="tab" href="#tab_' . ($tab['id'] ?? $i) . '">' . htmlspecialchars($tab['label'] ?? '') . '</a></li>';
                }
                $html .= '</ul><div class="tab-content mt-3">';
                foreach ($item['children'] ?? [] as $i => $tab) {
                    $active = $i === 0 ? 'show active' : '';
                    $html .= '<div class="tab-pane fade ' . $active . '" id="tab_' . ($tab['id'] ?? $i) . '">';
                    foreach ($tab['children'] ?? [] as $child) {
                        $html .= $this->renderLayoutItem($child, $fieldMap, $customFields);
                    }
                    $html .= '</div>';
                }
                $html .= '</div>';
                break;
            case 'column':
                $width = $item['width'] ?? 'full';
                $colMap = ['1/4' => 'col-md-3', '1/3' => 'col-md-4', '1/2' => 'col-md-6', '2/3' => 'col-md-8', '3/4' => 'col-md-9', 'full' => 'col-12'];
                $colClass = $colMap[$width] ?? 'col-12';
                $html .= '<div class="' . $colClass . '">';
                if (isset($item['field_id']) && isset($fieldMap[$item['field_id']])) {
                    $field = $fieldMap[$item['field_id']];
                    $value = $customFields[$field['field_name']] ?? ($field['default_value'] ?? '');
                    $fieldService = new ContentFieldService();
                    $html .= '<div class="mb-3"><label class="form-label">' . htmlspecialchars($field['field_label']);
                    if ($field['is_required']) $html .= ' <span class="text-danger">*</span>';
                    $html .= '</label>' . $fieldService->renderFormField($field, $value);
                    if ($field['help_text']) $html .= '<small class="form-text text-muted">' . htmlspecialchars($field['help_text']) . '</small>';
                    $html .= '</div>';
                }
                foreach ($item['children'] ?? [] as $child) {
                    $html .= $this->renderLayoutItem($child, $fieldMap, $customFields);
                }
                $html .= '</div>';
                break;
            case 'collapse':
                $html .= '<div class="accordion mb-3">';
                foreach ($item['children'] ?? [] as $i => $panel) {
                    $collapsed = $i > 0 ? 'collapsed' : '';
                    $show = $i === 0 ? 'show' : '';
                    $html .= '<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button ' . $collapsed . '" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_' . ($panel['id'] ?? $i) . '">' . htmlspecialchars($panel['label'] ?? '') . '</button></h2>';
                    $html .= '<div id="collapse_' . ($panel['id'] ?? $i) . '" class="accordion-collapse collapse ' . $show . '"><div class="accordion-body">';
                    foreach ($panel['children'] ?? [] as $child) {
                        $html .= $this->renderLayoutItem($child, $fieldMap, $customFields);
                    }
                    $html .= '</div></div></div>';
                }
                $html .= '</div>';
                break;
            case 'card':
                $html .= '<div class="card mb-3"><div class="card-header">' . htmlspecialchars($item['label'] ?? '') . '</div><div class="card-body">';
                foreach ($item['children'] ?? [] as $child) {
                    $html .= $this->renderLayoutItem($child, $fieldMap, $customFields);
                }
                $html .= '</div></div>';
                break;
            case 'divider':
                $html .= '<hr>';
                break;
            case 'note':
                $html .= '<div class="alert alert-info">' . htmlspecialchars($item['content'] ?? '') . '</div>';
                break;
            case 'html':
                $html .= $item['content'] ?? '';
                break;
            case 'row':
                $html .= '<div class="row">';
                foreach ($item['children'] ?? [] as $child) {
                    $html .= $this->renderLayoutItem($child, $fieldMap, $customFields);
                }
                $html .= '</div>';
                break;
            default:
                if (isset($item['field_id']) && isset($fieldMap[$item['field_id']])) {
                    $field = $fieldMap[$item['field_id']];
                    $value = $customFields[$field['field_name']] ?? ($field['default_value'] ?? '');
                    $fieldService = new ContentFieldService();
                    $html .= '<div class="mb-3"><label class="form-label">' . htmlspecialchars($field['field_label']) . '</label>';
                    $html .= $fieldService->renderFormField($field, $value);
                    $html .= '</div>';
                }
        }
        return $html;
    }

    public function renderPreview(int $modelId): string
    {
        return $this->renderForm($modelId, null);
    }

    public function saveTemplate(string $name, array $layout, string $category = ''): array
    {
        $dir = runtime_path() . 'form_templates';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $id = time();
        $template = ['id' => $id, 'name' => $name, 'category' => $category, 'layout' => $layout, 'created_at' => date('Y-m-d H:i:s')];
        file_put_contents($dir . '/' . $id . '.json', json_encode($template, JSON_UNESCAPED_UNICODE));
        return ['code' => 0, 'msg' => '保存成功', 'data' => ['id' => $id]];
    }

    public function getTemplates(string $category = ''): array
    {
        $dir = runtime_path() . 'form_templates';
        if (!is_dir($dir)) return [];
        $templates = [];
        foreach (glob($dir . '/*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                if ($category && ($data['category'] ?? '') !== $category) continue;
                $templates[] = $data;
            }
        }
        return $templates;
    }

    public function importTemplate(array $template): array
    {
        return $this->saveTemplate($template['name'] ?? '导入模板', $template['layout'] ?? [], $template['category'] ?? '');
    }

    public function exportTemplate(int $templateId): array
    {
        $file = runtime_path() . 'form_templates/' . $templateId . '.json';
        if (!file_exists($file)) return ['code' => 1, 'msg' => '模板不存在'];
        return ['code' => 0, 'data' => json_decode(file_get_contents($file), true)];
    }
}
