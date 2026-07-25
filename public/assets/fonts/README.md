# 本地化字体文件目录

> **版本**：V2.9.8+
> **用途**：存放模板定制面板使用的中文字体子集化 woff2 文件
> **最后更新**：2026-05-15

---

## 文件清单（需自行下载）

本目录需要以下 9 个 woff2 字体文件：

| 文件名 | 字体 | 字重 | 预估大小 | 来源 |
|:-------|:-----|:----:|:--------:|:-----|
| `noto-sans-sc-400.woff2` | Noto Sans SC (思源黑体) | 400 | ~350KB | Google Fonts Helper |
| `noto-sans-sc-700.woff2` | Noto Sans SC (思源黑体) | 700 | ~380KB | Google Fonts Helper |
| `noto-serif-sc-400.woff2` | Noto Serif SC (思源宋体) | 400 | ~400KB | Google Fonts Helper |
| `noto-serif-sc-700.woff2` | Noto Serif SC (思源宋体) | 700 | ~430KB | Google Fonts Helper |
| `lxgw-wenkai-400.woff2` | LXGW WenKai (霞鹜文楷) | 400 | ~450KB | Google Fonts Helper |
| `lxgw-wenkai-700.woff2` | LXGW WenKai (霞鹜文楷) | 700 | ~480KB | Google Fonts Helper |
| `ma-shan-zheng-400.woff2` | Ma Shan Zheng (马善政毛笔) | 400 | ~320KB | Google Fonts Helper |
| `inter-latin-400.woff2` | Inter | 400 | ~80KB | Google Fonts Helper |
| `inter-latin-700.woff2` | Inter | 700 | ~90KB | Google Fonts Helper |
| **总计** | | | **~2.9MB** | |

---

## 下载方法

### 方法一：Google Fonts Helper（推荐）

访问 https://gwfh.mranftl.com/fonts，搜索对应字体名称，选择以下选项：
- **Subset**: Chinese Simplified (如需覆盖所有中文字符)
- **Formats**: woff2 only
- **Styles**: 按上表选择字重
- 下载后重命名为上表文件名

### 方法二：官方仓库

| 字体 | 官方仓库 |
|:-----|:---------|
| Noto Sans SC | https://github.com/notofonts/noto-cjk |
| Noto Serif SC | https://github.com/notofonts/noto-cjk |
| LXGW WenKai | https://github.com/lxgw/LxgwWenKai |
| Ma Shan Zheng | https://github.com/google/fonts |
| Inter | https://github.com/rsms/inter |

从官方仓库下载后，使用 [fonttools](https://github.com/fonttools/fonttools) 子集化：

```bash
# 示例：子集化 Noto Sans SC
fonttools subset NotoSansSC-Regular.ttf \
  --unicodes="U+4E00-9FFF,U+3000-303F,U+FF00-FFEF,U+0020-007F" \
  --flavor=woff2 \
  --output-file=noto-sans-sc-400.woff2
```

---

## 验证

下载完成后，访问任意前台页面并打开浏览器开发者工具 → Network → Fonts，确认：
- 字体文件从 `/assets/fonts/` 加载
- Status 为 200
- font-display: swap 生效（加载期间文本可见）

---

## 离线测试

断开网络后刷新页面，确认：
- 中文字体仍正常渲染（非系统后备字体）
- 无网络请求失败（无 fonts.googleapis.com 请求）

---

> 如不想自行下载字体文件，可临时保留 Google Fonts CDN 引用作为回退方案。
> 但内网/离线环境必须配置本地字体文件才能正常显示。
