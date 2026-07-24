<?php
declare(strict_types=1);

namespace app\common\service\content;

use think\facade\Db;

class ContentImportExportService
{
    public function exportContents(int $modelId, array $filter = [], array $fieldSelection = [], string $format = 'csv'): array
    {
        try {
            $query = Db::name('content')->where('model_id', $modelId)->where('status', '>=', 0);
            foreach ($filter as $c) { if (is_array($c) && count($c) >= 3) $query->where($c[0], $c[1], $c[2]); }
            $total = $query->count();
            $allData = [];
            $batches = (int) ceil($total / 500);
            for ($i = 0; $i < $batches; $i++) {
                $rows = $query->limit($i * 500, 500)->select()->toArray();
                foreach ($rows as $row) {
                    $cf = json_decode($row['custom_fields'] ?? '{}', true) ?: [];
                    $rd = ['id'=>$row['id'],'title'=>$row['title'],'summary'=>$row['summary']??'','category_id'=>$row['category_id']??0,'status'=>$row['status'],'publish_time'=>$row['publish_time']??''];
                    foreach ($cf as $k=>$v) $rd[$k] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
                    $allData[] = $rd;
                }
            }
            $dir = runtime_path().'exports'; if(!is_dir($dir)) mkdir($dir,0777,true);
            $fName = 'export_'.$modelId.'_'.date('YmdHis').'.'.$format;
            $fPath = $dir.'/'.$fName;
            $content = $format==='json' ? $this->exportToJson($allData) : ($format==='excel' ? $this->exportToExcel($allData,[]) : $this->exportToCsv($allData,[]));
            file_put_contents($fPath,$content);
            return ['code'=>0,'msg'=>'导出成功','data'=>['file_path'=>$fPath,'file_name'=>$fName,'total'=>$total]];
        } catch (\Throwable $e) { return ['code'=>1,'msg'=>'导出失败: '.$e->getMessage()]; }
    }

    public function exportToCsv(array $data, array $headers): string
    {
        if(empty($headers)&&!empty($data)) $headers=array_keys($data[0]);
        $csv="\xEF\xBB\xBF".implode(',',$headers)."\n";
        foreach($data as $row){ $line=[]; foreach($headers as $h){ $v=str_replace(['"',"\n",','],['""',' ',' '],(string)($row[$h]??'')); $line[]='"'.$v.'"'; } $csv.=implode(',',$line)."\n"; }
        return $csv;
    }

    public function exportToJson(array $data): string { return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); }

    public function exportToExcel(array $data, array $headers): string
    {
        if(empty($headers)&&!empty($data)) $headers=array_keys($data[0]);
        $h='<table border="1"><tr>'; foreach($headers as $x) $h.='<th>'.htmlspecialchars($x).'</th>'; $h.='</tr>';
        foreach($data as $row){ $h.='<tr>'; foreach($headers as $x) $h.='<td>'.htmlspecialchars((string)($row[$x]??'')).'</td>'; $h.='</tr>'; }
        return $h.'</table>';
    }

    public function importContents(int $modelId, string $filePath, array $fieldMapping = [], string $duplicateAction = 'skip'): array
    {
        try {
            $ext=pathinfo($filePath,PATHINFO_EXTENSION);
            $data=$ext==='json'?$this->parseJson($filePath):$this->parseCsv($filePath);
            if(empty($data)) return ['code'=>1,'msg'=>'文件无数据'];
            $s=0;$sk=0;$u=0;$errs=[];
            foreach($data as $i=>$row){
                try{
                    $title=$row['title']??''; if(empty($title)){$errs[]="第".($i+2)."行:标题为空";continue;}
                    $ex=Db::name('content')->where('title',$title)->where('model_id',$modelId)->find();
                    if($ex){
                        if($duplicateAction==='skip'){$sk++;continue;}
                        elseif($duplicateAction==='update'){Db::name('content')->where('id',$ex['id'])->update($this->buildData($modelId,$row,$fieldMapping));$u++;continue;}
                        elseif($duplicateAction==='copy'){$row['title']=$title.'_副本'.time();}
                    }
                    Db::name('content')->insert($this->buildData($modelId,$row,$fieldMapping));$s++;
                }catch(\Throwable $e){$errs[]="第".($i+2)."行:".$e->getMessage();}
            }
            return ['code'=>0,'msg'=>"新增{$s},更新{$u},跳过{$sk},错误".count($errs),'data'=>['success'=>$s,'updated'=>$u,'skipped'=>$sk,'errors'=>$errs]];
        } catch(\Throwable $e){ return ['code'=>1,'msg'=>'导入失败: '.$e->getMessage()]; }
    }

    private function buildData(int $modelId, array $row, array $fieldMapping): array
    {
        $std=['title','summary','category_id','status','publish_time','content','seo_title','seo_keywords','seo_description'];
        $data=['model_id'=>$modelId,'model_identifier'=>(string)(Db::name('content_model')->where('id',$modelId)->value('model_identifier')?:'article')];
        $cf=[];
        foreach($row as $k=>$v){ if($k==='id')continue; $mk=$fieldMapping[$k]??$k; if(in_array($mk,$std)) $data[$mk]=$v; else $cf[$mk]=$v; }
        if(!empty($cf)) $data['custom_fields']=json_encode($cf,JSON_UNESCAPED_UNICODE);
        if(!isset($data['status'])) $data['status']=0;
        $data['create_time']=date('Y-m-d H:i:s');
        return $data;
    }

    public function parseCsv(string $filePath): array
    {
        $h=fopen($filePath,'r'); if(!$h) return [];
        $bom=fread($h,3); if($bom!=="\xEF\xBB\xBF") fseek($h,0);
        $headers=fgetcsv($h); if(!$headers){fclose($h);return [];}
        $data=[];
        while(($row=fgetcsv($h))!==false){ if(count($row)===count($headers)) $data[]=array_combine($headers,$row); }
        fclose($h); return $data;
    }

    public function parseJson(string $filePath): array { $d=json_decode(file_get_contents($filePath),true); return is_array($d)?$d:[]; }

    public function autoMapFields(array $importHeaders, int $modelId): array
    {
        $std=['title','summary','category_id','status','publish_time','content','seo_title','seo_keywords','seo_description'];
        $cf=\app\common\model\ContentField::getModelFields($modelId); $cn=array_column($cf,'field_name');
        $m=[];
        foreach($importHeaders as $h){ $l=strtolower($h); if(in_array($l,$std)) $m[$h]=$l; elseif(in_array($h,$cn)) $m[$h]=$h; else $m[$h]=$h; }
        return $m;
    }

    public function detectDuplicates(array $data, int $modelId): array
    {
        $d=[];
        foreach($data as $r){ $t=$r['title']??''; if(empty($t))continue; $ex=Db::name('content')->where('title',$t)->where('model_id',$modelId)->find(); if($ex) $d[]=['title'=>$t,'existing_id'=>$ex['id']]; }
        return $d;
    }

    public function batchOperation(string $op, array $ids, array $params = []): array
    {
        try {
            $c=0;
            switch($op){
                case 'publish': $c=Db::name('content')->whereIn('id',$ids)->update(['status'=>2,'publish_time'=>date('Y-m-d H:i:s')]); break;
                case 'delete': $c=Db::name('content')->whereIn('id',$ids)->update(['status'=>-1]); break;
                case 'moveCate': $c=Db::name('content')->whereIn('id',$ids)->update(['category_id'=>(int)($params['category_id']??0)]); break;
                case 'archive': $c=Db::name('content')->whereIn('id',$ids)->update(['status'=>3]); break;
                default: return ['code'=>1,'msg'=>'未知操作'];
            }
            return ['code'=>0,'msg'=>"影响{$c}条",'data'=>['count'=>$c]];
        } catch(\Throwable $e){ return ['code'=>1,'msg'=>'失败: '.$e->getMessage()]; }
    }

    public function getImportTemplate(int $modelId): array
    {
        $fields=\app\common\model\ContentField::getModelFields($modelId);
        $headers=['title','summary','category_id','status','publish_time'];
        foreach($fields as $f) $headers[]=$f['field_name'];
        $csv="\xEF\xBB\xBF".implode(',',$headers)."\n";
        $dir=runtime_path().'exports'; if(!is_dir($dir)) mkdir($dir,0777,true);
        $fName='import_template_'.$modelId.'.csv'; $fPath=$dir.'/'.$fName;
        file_put_contents($fPath,$csv);
        return ['code'=>0,'data'=>['file_path'=>$fPath,'file_name'=>$fName]];
    }
}
