<?php
declare(strict_types=1);
namespace app\home\controller;

use app\common\service\home\RssFeedService;
use think\Response;

class RssController extends \app\home\controller\BaseController
{
    public function feed(string $type = 'news')
    {
        $xml = RssFeedService::generateFeed($type, 20);
        return Response::create($xml)->contentType('application/rss+xml; charset=utf-8');
    }
}
