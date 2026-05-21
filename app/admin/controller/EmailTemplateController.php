<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\EmailTemplate;
use app\common\service\EmailService;

/**
 * 邮件模板管理后台控制器 - V2.5新增
 */
class EmailTemplateController extends AdminBaseController
{
    public function index()
    {
        $list = EmailTemplate::order('id', 'desc')
            ->paginate(['list_rows' => 20, 'path' => '/admin/email_template/index']);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list->toArray()]);
        }

        $this->assign('list', $list);
        return $this->view('/email_template_index');
    }

    public function add()
    {
        return $this->edit(0);
    }

    public function edit(int $id = 0)
    {
        $template = $id ? EmailTemplate::find($id) : null;
        $this->assign('info', $template);
        return $this->view('/email_template_edit');
    }

    public function save()
    {
        $data = [
            'id'         => (int) $this->request->post('id', 0),
            'name'       => $this->request->post('name', ''),
            'code'       => $this->request->post('code', ''),
            'subject'    => $this->request->post('subject', ''),
            'body'       => $this->request->post('body', ''),
            'vars'       => $this->request->post('vars', ''),
            'is_enabled' => (int) $this->request->post('is_enabled', 1),
        ];

        if (empty($data['name']) || empty($data['code'])) {
            return json(['code' => 1, 'msg' => '模板名称和编码不能为空']);
        }

        try {
            if ($data['id'] > 0) {
                $tpl = EmailTemplate::find($data['id']);
                if ($tpl) { $tpl->save($data); }
            } else {
                unset($data['id']);
                EmailTemplate::create($data);
            }
            \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_EMAIL);
            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            EmailTemplate::destroy($id);
            \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_EMAIL);
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 测试发送邮件
     */
    public function test()
    {
        $email = $this->request->post('email', '');
        if (empty($email)) {
            return json(['code' => 1, 'msg' => '请输入邮箱地址']);
        }

        try {
            $result = EmailService::testSend($email);
            return json(['code' => $result['success'] ? 0 : 1, 'msg' => $result['message']]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
