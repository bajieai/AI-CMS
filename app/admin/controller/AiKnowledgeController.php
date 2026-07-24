<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiKnowledgeBaseService;
use think\facade\Cache;

/**
 * AI知识库管理后台控制器 - V2.9.40 AI-DEEP2-4
 */
class AiKnowledgeController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 知识库列表
     */
    public function index()
    {
        $service = new AiKnowledgeBaseService();
        $list = $service->getList($this->request->get('page', 1), $this->request->get('limit', 20));
        $stats = $service->getStats();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => ['list' => $list, 'stats' => $stats]]);
        }

        $this->assign('list', $list);
        $this->assign('stats', $stats);
        return $this->view('/ai/knowledge_index');
    }

    /**
     * 创建知识库
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $data = [
                'name'             => $this->request->post('name', ''),
                'description'      => $this->request->post('description', ''),
                'type'             => $this->request->post('type', 'general'),
                'source_type'      => $this->request->post('source_type', 'manual'),
                'embedding_model'  => $this->request->post('embedding_model', 'tfidf'),
            ];
            if (empty($data['name'])) {
                return json(['code' => 1, 'msg' => '请输入知识库名称']);
            }

            $service = new AiKnowledgeBaseService();
            $id = $service->create($data);
            return json(['code' => 0, 'msg' => '知识库创建成功', 'data' => ['id' => $id]]);
        }

        return $this->view('/ai/knowledge_create');
    }

    /**
     * 知识库详情+文档列表
     */
    public function detail(int $id)
    {
        $service = new AiKnowledgeBaseService();
        $detail = $service->getDetail($id);
        if (!$detail) {
            return json(['code' => 1, 'msg' => '知识库不存在']);
        }

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $detail]);
        }

        $this->assign('detail', $detail);
        return $this->view('/ai/knowledge_detail');
    }

    /**
     * 导入文档
     */
    public function importDoc()
    {
        $kbId = (int) $this->request->post('kb_id', 0);
        $title = $this->request->post('title', '');
        $content = $this->request->post('content', '');

        if ($kbId <= 0 || empty($content)) {
            return json(['code' => 1, 'msg' => '请指定知识库和文档内容']);
        }

        $service = new AiKnowledgeBaseService();
        $docId = $service->importDocument($kbId, [
            'title'   => $title,
            'content' => $content,
            'source'  => 'manual',
        ]);

        return json(['code' => 0, 'msg' => '文档导入成功', 'data' => ['doc_id' => $docId]]);
    }

    /**
     * RAG检索测试
     */
    public function search()
    {
        $kbId = (int) $this->request->get('kb_id', 0);
        $query = $this->request->get('query', '');
        $topK = (int) $this->request->get('top_k', 5);

        if ($kbId <= 0 || empty($query)) {
            return json(['code' => 1, 'msg' => '请指定知识库和查询文本']);
        }

        $service = new AiKnowledgeBaseService();
        $results = $service->search($kbId, $query, $topK);
        return json(['code' => 0, 'msg' => 'success', 'data' => $results]);
    }

    /**
     * 删除知识库
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        $service = new AiKnowledgeBaseService();
        $service->delete($id);
        return json(['code' => 0, 'msg' => '知识库已删除']);
    }

    /**
     * 知识库配置
     */
    public function config()
    {
        $service = new AiKnowledgeBaseService();

        if ($this->request->isPost()) {
            $data = [
                'chunk_size'      => (int) $this->request->post('chunk_size', 500),
                'top_k'           => (int) $this->request->post('top_k', 5),
                'fulltext_weight' => (float) $this->request->post('fulltext_weight', 0.7),
                'tfidf_weight'    => (float) $this->request->post('tfidf_weight', 0.3),
            ];
            $service->saveConfig($data);
            return json(['code' => 0, 'msg' => '配置已保存']);
        }

        $config = $service->getConfig();
        return json(['code' => 0, 'msg' => 'success', 'data' => $config]);
    }
}
