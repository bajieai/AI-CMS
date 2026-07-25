#!/usr/bin/env python3
"""
AI-CMS Python SDK
V2.9.38 OPEN-PLAT-2
优先交付: Python SDK
后续版本: Ruby/Go/Java/.NET

安装: pip install ai-cms-sdk
使用: from ai_cms import AICmsClient
"""

import hashlib
import hmac
import json
import time
import uuid
import requests
from typing import Optional, Dict, List, Any, Union


class AICmsError(Exception):
    """AI-CMS SDK 异常"""
    def __init__(self, code: int, message: str, data: Any = None):
        self.code = code
        self.message = message
        self.data = data
        super().__init__(f"[{code}] {message}")


class AICmsClient:
    """AI-CMS API 客户端"""

    def __init__(self, api_key: str, api_secret: str, base_url: str = "https://your-domain.com/api/v1", timeout: int = 30):
        """
        初始化客户端
        
        Args:
            api_key: API Key (从开发者门户获取)
            api_secret: API Secret
            base_url: API基础URL
            timeout: 请求超时时间(秒)
        """
        self.api_key = api_key
        self.api_secret = api_secret
        self.base_url = base_url.rstrip('/')
        self.timeout = timeout
        self.max_retries = 3

    def _sign(self, method: str, path: str) -> Dict[str, str]:
        """HMAC-SHA256签名"""
        timestamp = str(int(time.time()))
        nonce = uuid.uuid4().hex
        string_to_sign = f"{method.upper()}{path}{timestamp}{nonce}"
        signature = hmac.new(
            self.api_secret.encode('utf-8'),
            string_to_sign.encode('utf-8'),
            hashlib.sha256
        ).hexdigest()
        return {
            'X-API-Key': self.api_key,
            'X-Timestamp': timestamp,
            'X-Nonce': nonce,
            'X-Signature': signature,
        }

    def _request(self, method: str, path: str, params: Optional[Dict] = None, data: Optional[Dict] = None) -> Dict:
        """发送HTTP请求(带重试)"""
        url = f"{self.base_url}{path}"
        headers = self._sign(method, path)
        headers['Content-Type'] = 'application/json'
        
        for attempt in range(self.max_retries):
            try:
                response = requests.request(
                    method=method.upper(),
                    url=url,
                    params=params,
                    json=data,
                    headers=headers,
                    timeout=self.timeout,
                )
                result = response.json()
                if result.get('code') == 0:
                    return result.get('data', {})
                else:
                    raise AICmsError(result.get('code', -1), result.get('msg', 'Unknown error'), result.get('data'))
            except requests.exceptions.RequestException as e:
                if attempt == self.max_retries - 1:
                    raise AICmsError(-1, f"Request failed: {str(e)}")
                time.sleep(2 ** attempt)

    # ===== 内容API =====
    def get_contents(self, page: int = 1, limit: int = 20, category_id: int = 0, keyword: str = "") -> Dict:
        """获取内容列表"""
        params = {'page': page, 'limit': limit}
        if category_id: params['category_id'] = category_id
        if keyword: params['keyword'] = keyword
        return self._request('GET', '/contents', params=params)

    def get_content(self, content_id: int) -> Dict:
        """获取内容详情"""
        return self._request('GET', f'/contents/{content_id}')

    def create_content(self, data: Dict) -> Dict:
        """创建内容"""
        return self._request('POST', '/contents', data=data)

    def update_content(self, content_id: int, data: Dict) -> Dict:
        """更新内容"""
        return self._request('PUT', f'/contents/{content_id}', data=data)

    def delete_content(self, content_id: int) -> Dict:
        """删除内容"""
        return self._request('DELETE', f'/contents/{content_id}')

    # ===== 分类API =====
    def get_categories(self) -> Dict:
        """获取分类列表"""
        return self._request('GET', '/categories')

    def get_category(self, category_id: int) -> Dict:
        """获取分类详情"""
        return self._request('GET', f'/categories/{category_id}')

    # ===== 文件API =====
    def upload_file(self, file_path: str) -> Dict:
        """上传文件"""
        url = f"{self.base_url}/files/upload"
        headers = self._sign('POST', '/files/upload')
        with open(file_path, 'rb') as f:
            files = {'file': f}
            response = requests.post(url, files=files, headers=headers, timeout=self.timeout)
            result = response.json()
            if result.get('code') == 0:
                return result.get('data', {})
            raise AICmsError(result.get('code', -1), result.get('msg', 'Upload failed'))

    def download_file(self, file_id: int) -> bytes:
        """下载文件"""
        url = f"{self.base_url}/files/{file_id}"
        headers = self._sign('GET', f'/files/{file_id}')
        response = requests.get(url, headers=headers, timeout=self.timeout, stream=True)
        return response.content

    # ===== 用户API =====
    def get_user(self, user_id: int) -> Dict:
        """获取用户信息"""
        return self._request('GET', f'/users/{user_id}')

    # ===== AI API =====
    def ai_write(self, prompt: str, **kwargs) -> Dict:
        """AI写作"""
        data = {'prompt': prompt, **kwargs}
        return self._request('POST', '/ai/write', data=data)

    def ai_translate(self, text: str, target_lang: str = 'en') -> Dict:
        """AI翻译"""
        return self._request('POST', '/ai/translate', data={'text': text, 'target_lang': target_lang})

    def ai_quality(self, content_id: int) -> Dict:
        """AI质检"""
        return self._request('POST', '/ai/quality', data={'content_id': content_id})

    # ===== 模板API =====
    def get_templates(self, page: int = 1, limit: int = 20) -> Dict:
        """获取模板列表"""
        return self._request('GET', '/templates', params={'page': page, 'limit': limit})
