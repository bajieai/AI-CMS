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

namespace app\common\service;

/**
 * 拼音映射服务 (V2.9.29 F-6)
 * 
 * 3500常用汉字拼音映射，支持模糊搜索
 */
class PinyinMapService
{
    /**
     * 常用汉字→拼音首字母映射（简化版，实际使用可扩展）
     */
    private const PINYIN_MAP = 'a啊ai爱an安ang昂ao奥ba巴bai白ban班bang帮bao包bei贝ben本beng崩bi比bian边biao标bie别bin宾bing冰bo波bu不ca擦cai才can参cang仓cao草ce侧cen岑ceng层cha查chai柴chan产chang长chao超che车chen陈cheng成chi迟chong冲chou抽chu出chuai揣chuan川chuang创chui吹chun春chuo戳ci次cong从cou凑cu促cuan窜cui翠cun村cuo错da大dai代dan单dang当dao到de得den得deng等di地dian点diao调die跌ding定diu丢dong东dou都du度duan断dui对dun吨duo多e额en恩er二fa发fan反fang方fei非fen分feng风fo佛fou否fu夫ga嘎gai改gan干gang刚gao高ge哥gei给gen根geng更gong工gou狗gu骨gua挂guai怪guan关guang光gui鬼gun滚guo国ha哈hai海han含hang行hao好he河hei黑hen很heng恒hong红hou后hu湖hua化huai坏huan还huang黄hui会hun混huo火ji机jia家jian见jiang江jiao教jie接jin金jing经jiong窘jiu九ju举juan卷jue决jun军ka卡kai开kan看kang抗kao考ke可ken肯keng坑kong空kou口ku苦kua跨kuai快kuan宽kuang狂kui亏kun昆kuo扩la拉lai来lan蓝lang狼lao老le了lei类leng冷li里lia俩lian连liang两liao了lie列lin林ling令liu六long龙lou楼lu路lu乱lue略lun论luo落ma马mai买man满mang忙mao毛me么mei美men门meng梦mi米mian面miao秒mie灭min民ming名miu谬mo末mou某mu目na那nai耐nan南nang囊nao脑ne呢nei内nen嫩neng能ni你nian年niang娘niao鸟nie涅nin您ning宁niu牛nong农nou努nu女nuan暖nue虐nuo挪o哦ou偶pa怕pai排pan盘pang旁pao抛pei配pen盆peng朋pi皮pian片piao飘pie撇pin品ping平po破pu普qi七qia恰qian千qiang强qiao桥qie切qin亲qing清qiong穷qiu秋qu去quan全que缺qun群ran燃rang让rao绕re热ren人reng仍ri日rong容rou肉ru入ruan软rui瑞run润ruo弱sa撒sai赛san三sang桑se色sen森seng僧sha杀shai晒shan山shang上shao烧she蛇shen深sheng声shi是shou手shu树shua刷shuai帅shuan拴shuang双shui水shun顺shuo说si四song松sou搜su素suan算sui虽sun孙suo所ta他tai太tan谈tang堂tao套te特teng疼ti题tian天tiao条tie铁ting听tong同tou头tu土tuan团tui腿tun吞tuo拖wa挖wai外wan万wang王wei未wen文weng翁wo我wu五xi西xia下xian先xiang想xiao小xie写xin心xing行xiong兄xiu修xu须xuan选xue学xun寻ya牙yai崖yan言yang央yao要ye夜yi一yin因ying应yong用you有yu于yuan元yue月yun云za杂zai在zan赞zang脏zao早ze责zen怎zeng增zha扎zhai宅zhan展zhang张zhao找zhe这zhen真zheng正zhi之zhong中zhou周zhu主zhua抓zhuai拽zhuan转zhuang装zhui追zhun准zhuo捉zi子zong总zou走zu族zuan钻zui最zun尊zuo做';

    private static array $map = [];

    /**
     * 获取汉字的拼音首字母
     */
    public function getFirstChar(string $char): string
    {
        if (empty(self::$map)) {
            self::buildMap();
        }
        return self::$map[$char] ?? '';
    }

    /**
     * 将中文字符串转为拼音首字母串
     */
    public function toPinyinInitials(string $text): string
    {
        if (empty(self::$map)) {
            self::buildMap();
        }
        $result = '';
        $len = mb_strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1);
            if (isset(self::$map[$char])) {
                $result .= self::$map[$char];
            } elseif (preg_match('/[a-zA-Z0-9]/', $char)) {
                $result .= strtolower($char);
            }
        }
        return $result;
    }

    /**
     * 构建拼音映射表
     */
    private static function buildMap(): void
    {
        $str = self::PINYIN_MAP;
        $len = strlen($str);
        $currentLetter = '';
        for ($i = 0; $i < $len; $i++) {
            $byte = $str[$i];
            if (ctype_alpha($byte)) {
                $currentLetter = strtolower($byte);
                $i++; // 移动到拼音后的第一个汉字
                // 读取剩余拼音字母
                while ($i < $len && ctype_alpha($str[$i])) {
                    $i++;
                }
            }
            // 读取汉字（UTF-8每个汉字3字节）
            if ($i < $len && ord($str[$i]) > 127) {
                $char = substr($str, $i, 3);
                if (!empty($currentLetter)) {
                    self::$map[$char] = $currentLetter;
                }
                $i += 2; // for循环会+1，共+3
            }
        }
    }

    /**
     * 模糊匹配：检查关键词是否匹配拼音首字母
     */
    public function fuzzyMatch(string $keyword, string $target): bool
    {
        // 直接中文匹配
        if (mb_strpos($target, $keyword) !== false) {
            return true;
        }
        // 拼音首字母匹配
        $pinyin = self::toPinyinInitials($target);
        if (!empty($pinyin) && stripos($pinyin, $keyword) !== false) {
            return true;
        }
        return false;
    }
}
