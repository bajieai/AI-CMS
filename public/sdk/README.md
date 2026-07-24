# AI-CMS 多语言 SDK

## V2.9.38 OPEN-PLAT-2 交付时间线

### 优先交付（V2.9.38）
- **Python SDK** — `python/ai_cms_sdk.py` ✅ 已完成
- **Node.js SDK** — `nodejs/ai-cms-sdk.js` ✅ 已完成

### 后续版本交付
- **Go SDK** — 计划 V2.9.39 交付
- **Java SDK** — 计划 V2.9.39 交付
- **.NET SDK** — 计划 V2.9.40 交付
- **Ruby SDK** — 计划 V2.9.40 交付

### 通用功能
- HMAC-SHA256 自动签名
- 自动重试（3次指数退避）
- 全API覆盖（内容/分类/文件/用户/AI/模板）
- 自定义异常和错误码
- 请求超时控制

### 使用示例

#### Python
```python
from ai_cms_sdk import AICmsClient

client = AICmsClient(api_key="your_key", api_secret="your_secret", base_url="https://your-domain.com/api/v1")
contents = client.get_contents(page=1, limit=20)
content = client.create_content({"title": "Hello", "content": "World"})
result = client.ai_write("写一篇关于AI的文章")
```

#### Node.js
```javascript
const { AICmsClient } = require('./ai-cms-sdk');

const client = new AICmsClient('your_key', 'your_secret', 'https://your-domain.com/api/v1');
const contents = await client.getContents(1, 20);
const content = await client.createContent({ title: 'Hello', content: 'World' });
const result = await client.aiWrite('写一篇关于AI的文章');
```
