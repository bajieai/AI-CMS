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
     * V2.9.19 S-1a: 恢复默认模板
     */
    public function resetDefault()
    {
        $code = $this->request->post('code', '');
        if (empty($code)) {
            return json(['code' => 1, 'msg' => '模板编码不能为空']);
        }

        $defaults = [
            'subscribe_confirm' => [
                'name'    => '订阅确认邮件',
                'subject' => '请确认订阅 {{site_name}} 的最新资讯',
                'body'    => '<h2>感谢您的订阅</h2><p>请点击下方链接确认订阅：</p><div style="text-align:center;margin:20px 0"><a href="{{confirm_url}}" style="background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px">确认订阅</a></div><hr><p style="color:#999;font-size:12px">如果您没有订阅，忽略此邮件即可。<br><a href="{{unsubscribe_url}}">退订</a></p>',
                'vars'    => 'site_name, confirm_url, unsubscribe_url, subscriber_email',
            ],
            'content_publish' => [
                'name'    => '内容发布通知',
                'subject' => '【{{site_name}}】新内容发布：{{content_title}}',
                'body'    => '<h2>{{content_title}}</h2>{{content_cover}}<p>{{content_summary}}</p><div style="text-align:center;margin:20px 0"><a href="{{content_url}}" style="background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px">查看详情</a></div><hr><p style="color:#999;font-size:12px">此邮件由 {{site_name}} 自动发送<br><a href="{{unsubscribe_url}}">退订</a></p>',
                'vars'    => 'site_name, content_title, content_summary, content_url, content_cover, unsubscribe_url, subscriber_email',
            ],
            'content_notify' => [
                'name'    => '内容发布通知',
                'subject' => '【{{site_name}}】新内容发布：{{content_title}}',
                'body'    => '<h2>{{content_title}}</h2>{{content_cover}}<p>{{content_summary}}</p><div style="text-align:center;margin:20px 0"><a href="{{content_url}}" style="background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px">查看详情</a></div><hr><p style="color:#999;font-size:12px">此邮件由 {{site_name}} 自动发送<br><a href="{{unsubscribe_url}}">退订</a></p>',
                'vars'    => 'site_name, content_title, content_summary, content_url, content_cover, unsubscribe_url, subscriber_email',
            ],
            'unsubscribe' => [
                'name'    => '退订确认',
                'subject' => '您已成功退订 {{site_name}} 的邮件通知',
                'body'    => '<h2>退订确认</h2><p>您已成功退订 <strong>{{site_name}}</strong> 的邮件通知，将不再收到相关内容推送。</p><p>如想重新订阅，请 <a href="{{subscribe_url}}">点击此处</a>。</p>',
                'vars'    => 'site_name, subscribe_url, subscriber_email',
            ],
        ];

        if (!isset($defaults[$code])) {
            return json(['code' => 1, 'msg' => '未知模板编码']);
        }

        $tpl = EmailTemplate::where('code', $code)->find();
        if (!$tpl) {
            return json(['code' => 1, 'msg' => '模板不存在']);
        }

        $def = $defaults[$code];
        $tpl->save([
            'name'    => $def['name'],
            'subject' => $def['subject'],
            'body'    => $def['body'],
            'vars'    => $def['vars'],
        ]);

        \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_EMAIL);
        return json(['code' => 0, 'msg' => '已恢复默认模板']);
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
