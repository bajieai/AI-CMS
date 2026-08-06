/**
 * SEO URL Auto-Fill
 * 用户输入分类名称时自动生成英文URL别名
 * 策略：常用词映射表 → 拼音首字母回退 → 保留词保护
 */
(function (global) {
    'use strict';

    // 系统保留词，自动生成的别名不能与这些冲突
    var RESERVED_WORDS = [
        'admin', 'api', 'install', 'index', 'home', 'login', 'logout',
        'register', 'search', 'member', 'user', 'page', 'captcha',
        'upload', 'download', 'sitemap', 'robots', 'static', 'assets',
        'skin', 'template', 'config', 'runtime', 'vendor', 'public'
    ];

    // 常见中文分类名称 → 英文映射表（优先匹配，生成更友好的URL）
    var CN_EN_MAP = {
        // 产品类
        '产品': 'products', '产品中心': 'products', '产品展示': 'products',
        '产品列表': 'products', '产品分类': 'products', '商品': 'products',
        '商品中心': 'products', '商品列表': 'products', '产品大全': 'products',
        // 案例类
        '案例': 'cases', '案例中心': 'cases', '案例展示': 'cases',
        '成功案例': 'cases', '客户案例': 'cases', '项目案例': 'cases',
        // 信息/新闻类
        '信息': 'info', '信息动态': 'info', '信息中心': 'info',
        '新闻': 'news', '新闻动态': 'news', '新闻中心': 'news',
        '资讯': 'news', '资讯中心': 'news', '行业资讯': 'news',
        '公告': 'notice', '公告通知': 'notice', '通知公告': 'notice',
        '动态': 'news', '公司动态': 'news', '企业动态': 'news',
        '公司新闻': 'news', '行业新闻': 'industry',
        // 下载类
        '下载': 'downloads', '下载中心': 'downloads', '资源下载': 'downloads',
        '资料下载': 'downloads', '文件下载': 'downloads',
        // 招聘类
        '招聘': 'jobs', '招聘信息': 'jobs', '人才招聘': 'jobs',
        '加入我们': 'careers', '诚聘英才': 'careers',
        // 单页类
        '关于我们': 'about', '关于': 'about', '公司简介': 'about',
        '企业简介': 'about', '联系我们': 'contact', '联系方式': 'contact',
        '服务条款': 'terms', '隐私政策': 'privacy', '免责声明': 'disclaimer',
        '帮助中心': 'help', '常见问题': 'faq', '问答': 'faq',
        '售后服务': 'service', '服务中心': 'service', '服务支持': 'service',
        '技术支持': 'support', '合作伙伴': 'partners',
        // 其他常见
        '首页': 'home', '主页': 'home', '网站首页': 'home',
        '视频': 'videos', '视频中心': 'videos', '视频展示': 'videos',
        '图片': 'gallery', '图片中心': 'gallery', '相册': 'gallery',
        '图库': 'gallery', '荣誉': 'honor', '荣誉资质': 'honor',
        '资质': 'certs', '资质证书': 'certs', '企业文化': 'culture',
        '发展历程': 'history'
    };

    /**
     * 内置拼音首字母表（常用500+字）
     * 覆盖最高频的中文分类用字
     */
    var PINYIN_INITIAL_MAP = {
        '产': 'c', '品': 'p', '中': 'z', '心': 'x', '展': 'z', '示': 's',
        '列': 'l', '表': 'b', '类': 'l', '分': 'f', '商': 's',
        '案': 'a', '例': 'l', '成': 'c', '功': 'g', '客': 'k', '户': 'h',
        '项': 'x', '目': 'm', '信': 'x', '息': 'x', '动': 'd', '态': 't',
        '新': 'x', '闻': 'w', '资': 'z', '讯': 'x', '行': 'x', '业': 'y',
        '公': 'g', '告': 'g', '通': 't', '知': 'z', '司': 's', '企': 'q',
        '下': 'x', '载': 'z', '源': 'y', '料': 'l', '文': 'w', '件': 'j',
        '招': 'z', '聘': 'p', '人': 'r', '才': 'c', '加': 'j', '入': 'r',
        '我': 'w', '们': 'm', '诚': 'c', '英': 'y',
        '关': 'g', '于': 'y', '简': 'j', '介': 'j', '联': 'l', '系': 'x',
        '方': 'f', '式': 's', '服': 'f', '务': 'w', '条': 't', '款': 'k',
        '隐': 'y', '私': 's', '政': 'z', '策': 'c', '免': 'm', '责': 'z',
        '声': 's', '明': 'm', '帮': 'b', '助': 'z', '常': 'c', '见': 'j',
        '问': 'w', '题': 't', '答': 'd', '售': 's', '后': 'h', '技': 'j',
        '术': 's', '支': 'z', '持': 'c', '合': 'h', '作': 'z', '伙': 'h',
        '伴': 'b', '首': 's', '页': 'y', '主': 'z', '网': 'w', '站': 'z',
        '视': 's', '频': 'p', '图': 't', '片': 'p', '相': 'x', '册': 'c',
        '库': 'k', '荣': 'r', '誉': 'y', '质': 'z', '证': 'z', '书': 's',
        '化': 'h', '发': 'f', '历': 'l', '程': 'c',
        '推': 't', '荐': 'j', '热': 'r', '门': 'm', '精': 'j', '选': 'x',
        '特': 't', '价': 'j', '优': 'y', '惠': 'h', '活': 'h',
        '专': 'z', '论': 'l', '坛': 't', '博': 'b', '客': 'k',
        '章': 'z', '社': 's', '区': 'q', '评': 'p', '留': 'l', '言': 'y',
        '反': 'f', '馈': 'k', '投': 't', '诉': 's', '盟': 'm',
        '校': 'x', '园': 'y', '教': 'j', '育': 'y', '培': 'p', '训': 'x',
        '课': 'k', '讲': 'j', '座': 'z', '报': 'b', '名': 'm',
        '预': 'y', '约': 'y', '订': 'd', '单': 'd', '购': 'g', '买': 'm',
        '付': 'f', '格': 'g', '清': 'q', '手': 's', '册': 'c',
        '指': 'z', '南': 'n', '导': 'd', '航': 'h', '地': 'd',
        '位': 'w', '置': 'z', '络': 'l', '邮': 'y', '箱': 'x',
        '电': 'd', '话': 'h', '传': 'c', '真': 'z', '址': 'z',
        '节': 'j', '点': 'd', '会': 'h', '议': 'y',
        '览': 'l', '沙': 's', '龙': 'l', '俱': 'j', '乐': 'l',
        '部': 'b', '团': 't', '队': 'd', '组': 'z', '织': 'z',
        '机': 'j', '构': 'g', '门': 'm', '院': 'y', '所': 's',
        '基': 'j', '旗': 'q', '子': 'z', '代': 'd', '理': 'l',
        '经': 'j', '销': 'x', '店': 'd', '铺': 'p', '舰': 'j',
        '体': 't', '验': 'y', '厅': 't', '卖': 'm', '场': 'c',
        '超': 'c', '市': 's', '广': 'g', '步': 'b', '街': 'j',
        '美': 'm', '食': 's', '餐': 'c', '饮': 'y', '酒': 'j',
        '宾': 'b', '馆': 'g', '旅': 'l', '景': 'j', '票': 'p',
        '车': 'c', '港': 'g', '口': 'k', '码': 'm', '头': 't',
        '安': 'a', '保': 'b', '障': 'z', '法': 'f', '规': 'g',
        '标': 'b', '签': 'q', '搜': 's', '索': 's', '归': 'g',
        '档': 'd', '回': 'h', '收': 's', '藏': 'c', '订': 'd',
        '阅': 'y', '推': 't', '送': 's', '消': 'x', '息': 'x',
        '交': 'j', '流': 'l', '互': 'h', '动': 'd', '享': 'x',
        '分': 'f', '赞': 'z', '藏': 'c', '评': 'p', '论': 'l',
        '好': 'h', '评': 'p', '差': 'c', '排': 'p', '行': 'x',
        '榜': 'b', '热': 'r', '销': 'x', '量': 'l', '额': 'e',
        // 城市名常用字
        '北': 'b', '京': 'j', '上': 's', '海': 'h', '广': 'g', '州': 'z',
        '深': 's', '圳': 'z', '杭': 'h', '苏': 's', '南': 'n', '东': 'd',
        '西': 'x', '武': 'w', '汉': 'h', '成': 'c', '重': 'c', '庆': 'q',
        '天': 't', '津': 'j', '长': 'c', '沙': 's', '沈': 's', '阳': 'y',
        '青': 'q', '岛': 'd', '大': 'd', '连': 'l', '宁': 'n', '波': 'b',
        '厦': 'x', '门': 'm', '福': 'f', '建': 'j', '昆': 'k', '明': 'm',
        '珠': 'z', '海': 'h', '佛': 'f', '山': 's', '东': 'd', '莞': 'g',
        '烟': 'y', '台': 't', '温': 'w', '舟': 'z', '苏': 's', '锡': 'x',
        '常': 'c', '州': 'z', '扬': 'y', '徐': 'x', '淮': 'h', '盐': 'y',
        '泰': 't', '镇': 'z', '宿': 's', '迁': 'q', '南': 'n', '通': 't',
        '湖': 'h', '湘': 'x', '江': 'j', '河': 'h', '洛': 'l', '开': 'k',
        '封': 'f', '安': 'a', '徽': 'h', '贵': 'g', '云': 'y', '藏': 'z',
        '陕': 's', '甘': 'g', '宁': 'n', '新': 'x', '内': 'n', '蒙': 'm',
        '桂': 'g', '琼': 'q', '赣': 'g', '浙': 'z', '鲁': 'l', '豫': 'y',
        '鄂': 'e', '湘': 'x', '冀': 'j', '晋': 'j', '辽': 'l', '吉': 'j',
        '黑': 'h', '台': 't', '港': 'g', '澳': 'a',
        // 方位/区域常用字
        '华': 'h', '东': 'd', '南': 'n', '西': 'x', '北': 'b', '中': 'z',
        '省': 's', '市': 's', '区': 'q', '县': 'x', '镇': 'z', '村': 'c',
        '街': 'j', '路': 'l', '号': 'h', '楼': 'l', '层': 'c', '室': 's',
        '院': 'y', '园': 'y', '场': 'c', '馆': 'g', '站': 'z', '所': 's',
        // 常见业务用字
        '集': 'j', '团': 't', '联': 'l', '盟': 'm', '总': 'z', '支': 'z',
        '分': 'f', '处': 'c', '办': 'b', '事': 's', '点': 'd', '部': 'b',
        '科': 'k', '研': 'y', '设': 's', '计': 'j', '制': 'z', '造': 'z',
        '工': 'g', '程': 'c', '建': 'j', '筑': 'z', '装': 'z', '饰': 's',
        '物': 'w', '流': 'l', '运': 'y', '输': 's', '仓': 'c', '储': 'c',
        '金': 'j', '融': 'r', '投': 't', '资': 'z', '担': 'd', '保': 'b',
        '咨': 'z', '询': 'x', '策': 'c', '划': 'h', '广': 'g', '告': 'g',
        '传': 'c', '媒': 'm', '公': 'g', '益': 'y', '慈': 'c', '善': 's'
    };

    /**
     * 哈希回退：用 Unicode 码点取模得到 a-z 中的某个字母
     */
    function guessByHash(code) {
        var letters = 'bcdfghjklmnpqrstwxyz';
        return letters.charAt(code % letters.length);
    }

    /**
     * 获取单个字符的拼音首字母
     */
    function getInitial(ch) {
        if (PINYIN_INITIAL_MAP[ch]) {
            return PINYIN_INITIAL_MAP[ch];
        }
        if (/[a-zA-Z0-9]/.test(ch)) {
            return ch.toLowerCase();
        }
        var code = ch.charCodeAt(0);
        if (code >= 0x4e00 && code <= 0x9fff) {
            return guessByHash(code);
        }
        return '';
    }

    /**
     * 检查并确保别名安全（不冲突保留词）
     */
    function ensureSafe(slug) {
        if (!slug) return '';
        slug = slug.toLowerCase().replace(/[^a-z0-9\-]/g, '');
        if (!slug) return '';
        // 确保以字母开头
        if (/^[0-9]/.test(slug)) {
            slug = 'c' + slug;
        }
        // 保留词检测
        if (RESERVED_WORDS.indexOf(slug) !== -1) {
            slug = slug + '1';
        }
        return slug;
    }

    /**
     * 生成英文URL别名
     * @param {string} name 分类名称
     * @returns {string} 生成的别名
     */
    function generateSeoUrl(name) {
        if (!name || typeof name !== 'string') return '';
        name = name.trim();
        if (!name) return '';

        // 1. 优先查映射表（精确匹配）
        if (CN_EN_MAP[name]) {
            return ensureSafe(CN_EN_MAP[name]);
        }

        // 去除常见中文后缀再查
        var suffixes = ['中心', '展示', '列表', '分类', '管理', '页面', '栏目', '区', '页'];
        for (var i = 0; i < suffixes.length; i++) {
            if (name.length > suffixes[i].length && name.endsWith(suffixes[i])) {
                var stripped = name.substring(0, name.length - suffixes[i].length);
                if (CN_EN_MAP[stripped]) {
                    return ensureSafe(CN_EN_MAP[stripped]);
                }
            }
        }

        // 2. 检查输入是否已经是英文
        if (/^[a-zA-Z0-9\s\-]+$/.test(name)) {
            var slug = name.toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            return ensureSafe(slug);
        }

        // 3. 拼音首字母方案
        var initials = '';
        for (var j = 0; j < name.length; j++) {
            var initial = getInitial(name.charAt(j));
            if (initial) {
                initials += initial;
            }
        }

        // 4. 截取合理长度
        if (initials.length > 20) {
            initials = initials.substring(0, 20);
        }

        return ensureSafe(initials || 'category');
    }

    /**
     * 初始化自动填充
     * @param {Object} options 配置项
     */
    function init(options) {
        options = options || {};
        var nameSelector = options.nameSelector || 'input[name="name"]';
        var seoUrlSelector = options.seoUrlSelector || 'input[name="seo_url"]';

        var nameInput = document.querySelector(nameSelector);
        var seoUrlInput = document.querySelector(seoUrlSelector);

        if (!nameInput || !seoUrlInput) return;

        // 标记：用户是否手动编辑过 seo_url
        var userTouched = false;
        var lastAutoValue = '';

        // 查重请求序号，防止旧请求覆盖新结果
        var checkSeq = 0;

        // 从表单 action 中提取 excludeId（编辑模式排除自身）
        var form = seoUrlInput.closest('form');
        var excludeId = 0;
        if (form) {
            var action = form.getAttribute('action') || '';
            var match = action.match(/\/cate\/edit\/(\d+)/);
            if (match) {
                excludeId = parseInt(match[1], 10);
            }
        }

        seoUrlInput.addEventListener('input', function () {
            userTouched = true;
        });

        /**
         * 异步查重：调用后端接口检查别名是否已存在
         * 冲突时自动替换为建议值
         */
        function checkDuplicate(slug) {
            var seq = ++checkSeq;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/admin/cate/checkSeoUrl?seo_url=' + encodeURIComponent(slug) + '&exclude_id=' + excludeId, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4 || seq !== checkSeq) return;
                if (xhr.status !== 200) return;
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.exists && res.suggestion) {
                        // 仅在当前值仍是自动生成值时才替换（用户可能已手动修改）
                        if (seoUrlInput.value.trim() === slug) {
                            seoUrlInput.value = res.suggestion;
                            lastAutoValue = res.suggestion;
                            flashHighlight(seoUrlInput);
                        }
                    }
                } catch (e) {}
            };
            xhr.send();
        }

        // blur 时触发（避免逐字干扰）
        nameInput.addEventListener('blur', function () {
            if (userTouched && seoUrlInput.value.trim()) {
                return;
            }
            var name = nameInput.value.trim();
            if (!name) return;
            var autoUrl = generateSeoUrl(name);
            if (autoUrl) {
                seoUrlInput.value = autoUrl;
                lastAutoValue = autoUrl;
                flashHighlight(seoUrlInput);
                // 异步查重，冲突时自动替换
                checkDuplicate(autoUrl);
            }
        });

        // input 事件实时生成（仅 seo_url 为空或等于上次自动值时）
        nameInput.addEventListener('input', function () {
            if (userTouched && seoUrlInput.value.trim()) {
                return;
            }
            var current = seoUrlInput.value.trim();
            if (!current || current === lastAutoValue) {
                var name = nameInput.value.trim();
                if (name) {
                    var autoUrl = generateSeoUrl(name);
                    if (autoUrl) {
                        seoUrlInput.value = autoUrl;
                        lastAutoValue = autoUrl;
                        // debounce 查重：input 时延迟查重避免频繁请求
                        var seq = ++checkSeq;
                        clearTimeout(nameInput._checkTimer);
                        nameInput._checkTimer = setTimeout(function () {
                            if (seq === checkSeq) {
                                checkDuplicate(autoUrl);
                            }
                        }, 500);
                    }
                }
            }
        });
    }

    /**
     * 绿色闪烁高亮提示
     */
    function flashHighlight(el) {
        el.style.transition = 'background-color 0.4s';
        el.style.backgroundColor = '#e8f5e9';
        setTimeout(function () {
            el.style.backgroundColor = '';
        }, 800);
    }

    // 导出
    global.SeoUrlAutofill = {
        init: init,
        generate: generateSeoUrl
    };

})(window);
