<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\exception\BusinessException;
use think\facade\Filesystem;
use think\facade\Config;
use think\facade\Image;

/**
 * 媒体控制器
 */
class MediaController extends BaseController
{
    /**
     * 上传文件
     */
    public function upload(): \think\Response
    {
        $file = $this->request->file('file');
        
        if (!$file) {
            throw new BusinessException('请选择要上传的文件', 400);
        }
        
        $config = Config::get('upload');
        
        // 验证文件
        $validate = [
            'size' => $config['max_size'],
            'ext' => $config['allowed_types'],
        ];
        
        try {
            $savename = Filesystem::disk('public')->putFile('uploads', $file, function () use ($config) {
                return date('Ymd') . '/' . uniqid();
            });
        } catch (\Exception $e) {
            throw new BusinessException('文件上传失败: ' . $e->getMessage(), 500);
        }
        
        $url = '/' . $savename;
        $path = runtime_path() . '..' . DIRECTORY_SEPARATOR . $savename;
        
        // 如果是图片，生成缩略图
        $thumbUrl = null;
        $isImage = in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        
        if ($isImage && !empty($config['image']['thumb'])) {
            $thumbUrl = $this->generateThumbnail($savename, $config['image']['thumb']);
        }
        
        // 保存到数据库
        $media = \think\facade\Db::name('media')->insertGetId([
            'user_id' => $this->request->user_id,
            'original_name' => $file->getOriginalName(),
            'file_name' => $savename,
            'file_path' => $url,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMime(),
            'file_ext' => $file->getExtension(),
            'is_image' => $isImage ? 1 : 0,
            'thumb_url' => $thumbUrl,
            'create_time' => date('Y-m-d H:i:s'),
        ]);
        
        return $this->success([
            'id' => $media,
            'url' => asset_url($url),
            'thumb_url' => $thumbUrl ? asset_url($thumbUrl) : null,
            'original_name' => $file->getOriginalName(),
            'file_size' => $file->getSize(),
            'file_size_text' => format_file_size($file->getSize()),
            'mime_type' => $file->getMime(),
            'is_image' => $isImage,
        ], '上传成功', 201);
    }

    /**
     * 媒体列表
     */
    public function index(): \think\Response
    {
        $pageParams = $this->getPageParams();
        $type = $this->request->param('type', ''); // image/video/audio/file
        $keyword = $this->getSearchParams();
        
        $query = \think\facade\Db::name('media')->where('user_id', '=', $this->request->user_id);
        
        if ($type) {
            switch ($type) {
                case 'image':
                    $query->where('is_image', '=', 1);
                    break;
                case 'video':
                    $query->where('file_ext', 'in', 'mp4,avi,mov,wmv,flv,webm');
                    break;
                case 'audio':
                    $query->where('file_ext', 'in', 'mp3,wav,ogg,aac,m4a');
                    break;
                case 'file':
                    $query->where('is_image', '=', 0)
                        ->whereNotIn('file_ext', 'mp4,avi,mov,wmv,flv,webm,mp3,wav,ogg,aac,m4a');
                    break;
            }
        }
        
        if ($keyword) {
            $query->whereLike('original_name', "%{$keyword}%");
        }
        
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($pageParams['page'], $pageParams['per_page'])
            ->select();
        
        // 处理URL
        $data = [];
        foreach ($list as $item) {
            $item['url'] = asset_url($item['file_path']);
            $item['thumb_url'] = $item['thumb_url'] ? asset_url($item['thumb_url']) : null;
            $item['file_size_text'] = format_file_size($item['file_size']);
            $data[] = $item;
        }
        
        return $this->paginate($data, $total, $pageParams['page'], $pageParams['per_page']);
    }

    /**
     * 查看媒体
     */
    public function read(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少媒体ID', 400);
        }
        
        $media = \think\facade\Db::name('media')->find($id);
        
        if (!$media) {
            throw new BusinessException('媒体不存在', 404);
        }
        
        $media['url'] = asset_url($media['file_path']);
        $media['thumb_url'] = $media['thumb_url'] ? asset_url($media['thumb_url']) : null;
        $media['file_size_text'] = format_file_size($media['file_size']);
        
        return $this->success($media);
    }

    /**
     * 删除媒体
     */
    public function delete(): \think\Response
    {
        $id = $this->getIdParam();
        
        if (!$id) {
            throw new BusinessException('缺少媒体ID', 400);
        }
        
        $media = \think\facade\Db::name('media')->find($id);
        
        if (!$media) {
            throw new BusinessException('媒体不存在', 404);
        }
        
        // 检查权限
        if ($media['user_id'] != $this->request->user_id) {
            throw new BusinessException('无权删除该媒体', 403);
        }
        
        // 删除文件
        $filePath = runtime_path() . '..' . DIRECTORY_SEPARATOR . $media['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // 删除缩略图
        if ($media['thumb_url']) {
            $thumbPath = runtime_path() . '..' . DIRECTORY_SEPARATOR . $media['thumb_url'];
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }
        
        // 删除数据库记录
        \think\facade\Db::name('media')->delete($id);
        
        return $this->success(null, '删除成功');
    }

    /**
     * 生成缩略图
     */
    protected function generateThumbnail(string $imagePath, array $config): ?string
    {
        try {
            $sourcePath = runtime_path() . '..' . DIRECTORY_SEPARATOR . $imagePath;
            $imageInfo = pathinfo($imagePath);
            
            $thumbDir = $imageInfo['dirname'] . '/thumb_' . $config['width'] . 'x' . $config['height'];
            $thumbName = $imageInfo['filename'] . '_thumb.' . $imageInfo['extension'];
            $thumbPath = runtime_path() . '..' . DIRECTORY_SEPARATOR . $thumbDir;
            
            if (!is_dir($thumbPath)) {
                mkdir($thumbPath, 0755, true);
            }
            
            $thumbFullPath = $thumbPath . DIRECTORY_SEPARATOR . $thumbName;
            
            // 使用GD库生成缩略图
            $image = Image::open($sourcePath);
            $image->thumb($config['width'], $config['height'], \think\Image::THUMB_CENTER)
                ->save($thumbFullPath, null, $config['quality'] ?? 85);
            
            return $thumbDir . '/' . $thumbName;
        } catch (\Exception $e) {
            trace('缩略图生成失败: ' . $e->getMessage(), 'error');
            return null;
        }
    }
}
