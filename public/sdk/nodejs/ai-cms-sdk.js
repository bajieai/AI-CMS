/**
 * AI-CMS Node.js SDK
 * V2.9.38 OPEN-PLAT-2
 * 优先交付: Node.js SDK
 * 后续版本: Ruby/Go/Java/.NET
 *
 * 安装: npm install ai-cms-sdk
 * 使用: const { AICmsClient } = require('ai-cms-sdk');
 */

const crypto = require('crypto');
const https = require('https');
const http = require('http');

class AICmsError extends Error {
    constructor(code, message, data = null) {
        super(`[${code}] ${message}`);
        this.code = code;
        this.data = data;
    }
}

class AICmsClient {
    /**
     * 初始化客户端
     * @param {string} apiKey - API Key
     * @param {string} apiSecret - API Secret
     * @param {string} baseUrl - API基础URL
     * @param {number} timeout - 超时时间(毫秒)
     */
    constructor(apiKey, apiSecret, baseUrl = 'https://your-domain.com/api/v1', timeout = 30000) {
        this.apiKey = apiKey;
        this.apiSecret = apiSecret;
        this.baseUrl = baseUrl.replace(/\/+$/, '');
        this.timeout = timeout;
        this.maxRetries = 3;
    }

    /**
     * HMAC-SHA256签名
     */
    _sign(method, path) {
        const timestamp = Math.floor(Date.now() / 1000).toString();
        const nonce = crypto.randomBytes(16).toString('hex');
        const stringToSign = `${method.toUpperCase()}${path}${timestamp}${nonce}`;
        const signature = crypto.createHmac('sha256', this.apiSecret).update(stringToSign).digest('hex');
        return {
            'X-API-Key': this.apiKey,
            'X-Timestamp': timestamp,
            'X-Nonce': nonce,
            'X-Signature': signature,
        };
    }

    /**
     * 发送HTTP请求
     */
    async _request(method, path, params = null, data = null) {
        let url = `${this.baseUrl}${path}`;
        if (params) {
            const qs = new URLSearchParams(params).toString();
            url += `?${qs}`;
        }

        for (let attempt = 0; attempt < this.maxRetries; attempt++) {
            try {
                const headers = this._sign(method, path);
                headers['Content-Type'] = 'application/json';

                const response = await this._httpRequest(method, url, headers, data);
                const result = JSON.parse(response);
                if (result.code === 0) {
                    return result.data || {};
                }
                throw new AICmsError(result.code || -1, result.msg || 'Unknown error', result.data);
            } catch (e) {
                if (attempt === this.maxRetries - 1) throw e;
                await new Promise(r => setTimeout(r, Math.pow(2, attempt) * 1000));
            }
        }
    }

    _httpRequest(method, url, headers, data) {
        return new Promise((resolve, reject) => {
            const urlObj = new URL(url);
            const lib = urlObj.protocol === 'https:' ? https : http;
            const options = {
                method: method.toUpperCase(),
                hostname: urlObj.hostname,
                port: urlObj.port,
                path: urlObj.pathname + urlObj.search,
                headers,
                timeout: this.timeout,
            };
            const req = lib.request(options, (res) => {
                let body = '';
                res.on('data', (chunk) => body += chunk);
                res.on('end', () => resolve(body));
            });
            req.on('error', reject);
            req.on('timeout', () => req.destroy(new Error('Request timeout')));
            if (data) req.write(JSON.stringify(data));
            req.end();
        });
    }

    // ===== 内容API =====
    getContents(page = 1, limit = 20, categoryId = 0, keyword = '') {
        const params = { page, limit };
        if (categoryId) params.category_id = categoryId;
        if (keyword) params.keyword = keyword;
        return this._request('GET', '/contents', params);
    }

    getContent(contentId) {
        return this._request('GET', `/contents/${contentId}`);
    }

    createContent(data) {
        return this._request('POST', '/contents', null, data);
    }

    updateContent(contentId, data) {
        return this._request('PUT', `/contents/${contentId}`, null, data);
    }

    deleteContent(contentId) {
        return this._request('DELETE', `/contents/${contentId}`);
    }

    // ===== 分类API =====
    getCategories() {
        return this._request('GET', '/categories');
    }

    getCategory(categoryId) {
        return this._request('GET', `/categories/${categoryId}`);
    }

    // ===== 用户API =====
    getUser(userId) {
        return this._request('GET', `/users/${userId}`);
    }

    // ===== AI API =====
    aiWrite(prompt, options = {}) {
        return this._request('POST', '/ai/write', null, { prompt, ...options });
    }

    aiTranslate(text, targetLang = 'en') {
        return this._request('POST', '/ai/translate', null, { text, target_lang: targetLang });
    }

    aiQuality(contentId) {
        return this._request('POST', '/ai/quality', null, { content_id: contentId });
    }

    // ===== 模板API =====
    getTemplates(page = 1, limit = 20) {
        return this._request('GET', '/templates', { page, limit });
    }
}

module.exports = { AICmsClient, AICmsError };
