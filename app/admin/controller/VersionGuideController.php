<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\VersionGuideService;

class VersionGuideController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    public function show()
    {
        $userId = (int) session('user_id');
        $service = new VersionGuideService();

        if (!$service->needsGuide($userId)) {
            return json(['show' => false]);
        }

        $guide = $service->getGuideContent();
        $service->markViewed($userId);

        return json(['show' => true, 'guide' => $guide]);
    }

    public function dismiss()
    {
        $userId = (int) session('user_id');
        $service = new VersionGuideService();
        $service->markViewed($userId);
        return $this->success('已关闭');
    }
}
