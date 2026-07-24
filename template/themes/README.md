# 模板主题目录规范

> V2.9.9 目录标准化文档

---

## 一、目录结构

```
template/themes/
├── README.md                 # 本文件
├── corporate/                # V2 格式示例（系统内置）
│   ├── theme.json            # 主题元数据
│   ├── index.html
│   ├── list.html
│   ├── detail.html
│   └── ...
├── default/                  # V2 格式示例（系统内置）
│   ├── theme.json
│   └── ...
├── ai-theme-{date}-{hash}/   # V3 格式（AI自动生成）
│   ├── theme.json
│   ├── pc/
│   │   ├── layout.html
│   │   ├── index.html
│   │   ├── list.html
│   │   └── detail.html
│   ├── mobile/
│   │   ├── layout.html
│   │   └── index.html
│   ├── assets/
│   │   ├── css/style.css
│   │   └── js/main.js
│   └── images/
└── {industry}-base/          # V2.9.9 F-4 行业基底（推荐结构）
    └── theme.json            # 含完整 market standard 字段
```

---

## 二、theme.json 格式规范

### 2.1 两种兼容格式

| 格式 | 识别特征 | 说明 |
|:-----|:---------|:-----|
| **V2** | 含 `colors` + `options` | 系统内置主题（corporate/default） |
| **V3** | 含 `layouts` + `assets` + `pages` | AI自动生成主题 |

### 2.2 市场标准字段（推荐）

```json
{
  "name": "主题名称",
  "version": "1.0.0",
  "description": "主题描述",
  "author": "作者名",
  "category": "industry",      // 行业分类
  "tags": ["tag1", "tag2"],    // 标签
  "preview": "/path/to/preview.png",
  "type": "enterprise",        // 类型: enterprise/ecommerce/blog/portal/education
  "supports": ["pc", "mobile"],
  "colors": {
    "primary": "#3b82f6",
    "secondary": "#64748b"
  },
  "layouts": {
    "pc": "pc/layout.html",
    "mobile": "mobile/layout.html"
  },
  "assets": {
    "css": "assets/css/style.css",
    "js": "assets/js/main.js"
  }
}
```

### 2.3 校验工具

```bash
# 校验所有主题
php think theme:validate

# 校验单个主题
php think theme:validate corporate

# 严格模式（warning也视为失败）
php think theme:validate --strict

# 输出JSON
php think theme:validate --json
```

---

## 三、命名规范

| 项目 | 规范 | 示例 |
|:-----|:-----|:-----|
| 目录名 | 小写 + 连字符 | `corporate`, `ai-theme-20260514-abc123` |
| theme.json | 必须存在 | 主题根目录下 |
| 页面文件 | 小写 + 下划线 | `index.html`, `list.html`, `detail.html` |
| 静态资源 | 按类型分目录 | `assets/css/`, `assets/js/`, `images/` |

---

## 四、迁移指南

### V2 → V3 迁移（可选）

1. 保留 `colors` 和 `options`（向后兼容）
2. 新增 `layouts` + `assets` + `pages`
3. 补充 `category` + `tags` + `preview`
4. 运行 `php think theme:validate --strict` 确认

---

*本文档随 V2.9.9 版本发布。*
