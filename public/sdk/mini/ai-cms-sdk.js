/**
 * AI-CMS 小程序前端 SDK v1.0.0
 * V2.9.37 MINI-FULL-1
 * 
 * 使用方式:
 * const AICMS = require('./ai-cms-sdk.js');
 * AICMS.init({ baseUrl: 'https://cms.example.com', appId: 'xxx' });
 * AICMS.content.getList({ model: 'product', page: 1 });
 */

const AICMS = (function () {
    var config = { baseUrl: '', appId: '', timeout: 15000 };
    var token = '';
    var refreshToken = '';

    // ========== HTTP 模块 ==========
    var http = {
        request: function (options) {
            return new Promise(function (resolve, reject) {
                var header = options.header || {};
                header['Content-Type'] = header['Content-Type'] || 'application/json';
                if (token) header['Authorization'] = 'Bearer ' + token;
                header['X-App-Id'] = config.appId;
                header['X-Timestamp'] = Date.now();

                wx.request({
                    url: config.baseUrl + options.url,
                    method: options.method || 'GET',
                    data: options.data || {},
                    header: header,
                    timeout: options.timeout || config.timeout,
                    success: function (res) {
                        if (res.statusCode === 401 && token) {
                            // Token过期，尝试刷新
                            auth.refreshToken().then(function () {
                                http.request(options).then(resolve).catch(reject);
                            }).catch(function () {
                                token = '';
                                wx.removeStorageSync('aicms_token');
                                reject(new Error('Token已过期，请重新登录'));
                            });
                        } else if (res.statusCode >= 200 && res.statusCode < 300) {
                            resolve(res.data);
                        } else {
                            reject(new Error('HTTP ' + res.statusCode + ': ' + (res.data && res.data.msg || '请求失败')));
                        }
                    },
                    fail: function (err) {
                        reject(new Error(err.errMsg || '网络请求失败'));
                    }
                });
            });
        },
        get: function (url, data) { return http.request({ url: url, method: 'GET', data: data }); },
        post: function (url, data) { return http.request({ url: url, method: 'POST', data: data }); },
        upload: function (url, filePath, name, formData) {
            return new Promise(function (resolve, reject) {
                wx.uploadFile({
                    url: config.baseUrl + url,
                    filePath: filePath,
                    name: name || 'file',
                    formData: formData || {},
                    header: token ? { 'Authorization': 'Bearer ' + token } : {},
                    success: function (res) {
                        try { resolve(JSON.parse(res.data)); } catch (e) { resolve(res.data); }
                    },
                    fail: reject
                });
            });
        }
    };

    // ========== Auth 模块 ==========
    var auth = {
        login: function () {
            return new Promise(function (resolve, reject) {
                wx.login({
                    success: function (loginRes) {
                        if (!loginRes.code) { reject(new Error('微信登录失败')); return; }
                        http.post('/api/mini/v1/user/login', { code: loginRes.code }).then(function (res) {
                            if (res.code === 0 && res.data) {
                                token = res.data.token || '';
                                refreshToken = res.data.refresh_token || '';
                                wx.setStorageSync('aicms_token', token);
                                if (refreshToken) wx.setStorageSync('aicms_refresh_token', refreshToken);
                                resolve(res.data);
                            } else { reject(new Error(res.msg || '登录失败')); }
                        }).catch(reject);
                    },
                    fail: reject
                });
            });
        },
        refreshToken: function () {
            var rt = wx.getStorageSync('aicms_refresh_token');
            if (!rt) return Promise.reject(new Error('无refresh_token'));
            return http.post('/api/mini/v1/user/refresh', { refresh_token: rt }).then(function (res) {
                if (res.code === 0 && res.data && res.data.token) {
                    token = res.data.token;
                    wx.setStorageSync('aicms_token', token);
                    return res.data;
                }
                throw new Error('刷新Token失败');
            });
        },
        getUserInfo: function () { return http.get('/api/mini/v1/user/info'); },
        update: function (data) { return http.post('/api/mini/v1/user/update', data); },
        isLoggedIn: function () { return !!token; },
        logout: function () {
            token = ''; refreshToken = '';
            wx.removeStorageSync('aicms_token');
            wx.removeStorageSync('aicms_refresh_token');
        }
    };

    // ========== Content 模块 ==========
    var content = {
        getList: function (params) { return http.get('/api/mini/v1/content/list', params); },
        getDetail: function (id) { return http.get('/api/mini/v1/content/detail', { id: id }); },
        search: function (keyword, page) { return http.get('/api/mini/v1/content/search', { keyword: keyword, page: page || 1 }); },
        category: function (type) { return http.get('/api/mini/v1/content/category', { type: type }); },
        tag: function (name) { return http.get('/api/mini/v1/content/tag', { name: name }); },
        recommend: function (limit) { return http.get('/api/mini/v1/content/recommend', { limit: limit || 10 }); },
        hot: function (limit) { return http.get('/api/mini/v1/content/hot', { limit: limit || 10 }); },
        related: function (id) { return http.get('/api/mini/v1/content/related', { id: id }); }
    };

    // ========== Interaction 模块 ==========
    var interaction = {
        favorite: {
            list: function (page) { return http.get('/api/mini/v1/user/favorite', { page: page || 1 }); },
            add: function (contentId) { return http.post('/api/mini/v1/user/favorite/add', { content_id: contentId }); },
            remove: function (contentId) { return http.post('/api/mini/v1/user/favorite/remove', { content_id: contentId }); }
        },
        like: function (contentId) { return http.post('/api/mini/v1/user/like', { content_id: contentId }); },
        comment: {
            submit: function (contentId, content) { return http.post('/api/mini/v1/user/comment', { content_id: contentId, content: content }); },
            list: function (contentId, page) { return http.get('/api/mini/v1/user/comment/list', { content_id: contentId, page: page || 1 }); }
        }
    };

    // ========== Cache 模块 ==========
    var cache = {
        set: function (key, data, ttl) {
            var item = { data: data, expire: ttl ? Date.now() + ttl * 1000 : 0 };
            wx.setStorageSync('aicms_' + key, item);
        },
        get: function (key) {
            var item = wx.getStorageSync('aicms_' + key);
            if (!item) return null;
            if (item.expire && Date.now() > item.expire) { wx.removeStorageSync('aicms_' + key); return null; }
            return item.data;
        },
        remove: function (key) { wx.removeStorageSync('aicms_' + key); },
        clear: function () {
            var info = wx.getStorageInfoSync();
            info.keys.forEach(function (k) { if (k.indexOf('aicms_') === 0) wx.removeStorageSync(k); });
        }
    };

    // ========== Share 模块 ==========
    var share = {
        configure: function (options) {
            if (typeof onPageShare === 'function') return;
            // 配置分享内容
            this._shareConfig = options || {};
        },
        callback: function (cb) { this._callback = cb; },
        getShareInfo: function () { return this._shareConfig || {}; }
    };

    // ========== Analytics 模块 ==========
    var analytics = {
        track: function (eventType, data) {
            return http.post('/api/mini/v1/stats/track', Object.assign({ event_type: eventType }, data || {}));
        },
        pageView: function (pageType, pagePath) {
            return analytics.track('page_view', { page_type: pageType, page_path: pagePath });
        },
        event: function (name, params) {
            return analytics.track(name, params);
        }
    };

    // ========== Utils 模块 ==========
    var utils = {
        formatDate: function (date, fmt) {
            if (typeof date === 'number' || typeof date === 'string') date = new Date(date);
            fmt = fmt || 'YYYY-MM-DD HH:mm';
            var o = {
                'YYYY': date.getFullYear(), 'MM': String(date.getMonth() + 1).padStart(2, '0'),
                'DD': String(date.getDate()).padStart(2, '0'), 'HH': String(date.getHours()).padStart(2, '0'),
                'mm': String(date.getMinutes()).padStart(2, '0'), 'ss': String(date.getSeconds()).padStart(2, '0')
            };
            var result = fmt;
            for (var k in o) result = result.replace(k, o[k]);
            return result;
        },
        truncate: function (str, len, suffix) {
            if (!str) return '';
            if (str.length <= len) return str;
            return str.substring(0, len) + (suffix || '...');
        },
        formatPrice: function (price, decimals) {
            if (isNaN(price)) return '0.00';
            return parseFloat(price).toFixed(decimals || 2);
        },
        escapeHtml: function (str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    };

    // ========== Init ==========
    function init(options) {
        config.baseUrl = options.baseUrl || '';
        config.appId = options.appId || '';
        config.timeout = options.timeout || 15000;
        // 恢复Token
        var savedToken = wx.getStorageSync('aicms_token');
        if (savedToken) token = savedToken;
        var savedRefresh = wx.getStorageSync('aicms_refresh_token');
        if (savedRefresh) refreshToken = savedRefresh;
    }

    return {
        init: init,
        http: http,
        auth: auth,
        content: content,
        interaction: interaction,
        cache: cache,
        share: share,
        analytics: analytics,
        utils: utils,
        version: '1.0.0',
        getConfig: function () { return config; }
    };
})();

module.exports = AICMS;
