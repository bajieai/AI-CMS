/**
 * V2.9.27 T-1: SSE 客户端 v2
 * 
 * 升级特性：
 * - Last-Event-Id 断线重连补推（V2.9.27 T-5）
 * - notification 通道支持（V2.9.27 T-2）
 * - 连接ID管理（V2.9.27 T-4）
 * 
 * 保留特性（V2.9.20）：
 * - EventSource 自动连接
 * - 指数退避重连（1s -> 2s -> 4s -> 8s -> 最大60s）
 * - 心跳包过滤（30秒间隔，60秒超时触发重连）
 * - 轮询降级（EventSource 不支持时自动降级为长轮询）
 */
(function(global) {
    'use strict';

    function SseClient(options) {
        this.url = options.url || '/api/sse/stream?channel=system';
        this.channel = options.channel || 'system';
        this.onMessage = options.onMessage || function() {};
        this.onConnect = options.onConnect || function() {};
        this.onDisconnect = options.onDisconnect || function() {};
        this.onError = options.onError || function() {};

        this.eventSource = null;
        this.reconnectTimer = null;
        this.reconnectAttempts = 0;
        this.maxReconnectDelay = 60000;
        this.heartbeatTimeout = 60000;
        this.heartbeatTimer = null;
        this.lastHeartbeat = 0;
        this.pollingMode = false;
        this.pollingTimer = null;
        this.lastEventTime = 0;
        this.destroyed = false;
        // V2.9.27 T-5: Last-Event-Id 断线重连
        this.lastEventId = 0;
        this.clientId = 'sse_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    SseClient.prototype.connect = function() {
        if (this.destroyed) return;
        this.disconnect();
        if (typeof EventSource !== 'undefined') {
            this._connectEventSource();
        } else {
            this._startPolling();
        }
    };

    SseClient.prototype._connectEventSource = function() {
        var self = this;
        // V2.9.27 T-5: 传递Last-Event-Id和client_id
        var url = this.url + (this.url.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now() + '&client_id=' + this.clientId;
        if (this.lastEventId > 0) {
            url += '&last_event_id=' + this.lastEventId;
        }

        try {
            this.eventSource = new EventSource(url);
            this.lastHeartbeat = Date.now();
            this.lastEventTime = Date.now();

            this.eventSource.addEventListener('connected', function(e) {
                self.reconnectAttempts = 0;
                var data = JSON.parse(e.data || '{}');
                if (data.client_id) self.clientId = data.client_id;
                self.onConnect(data);
                self._startHeartbeatCheck();
            });

            this.eventSource.addEventListener('heartbeat', function(e) {
                self.lastHeartbeat = Date.now();
            });

            // V2.9.27: notification 通道（T-2）
            this.eventSource.addEventListener('notification', function(e) {
                self.lastEventTime = Date.now();
                self._updateLastEventId(e);
                self.onMessage('notification', JSON.parse(e.data || '{}'));
            });

            this.eventSource.addEventListener('unread_update', function(e) {
                self.lastEventTime = Date.now();
                self._updateLastEventId(e);
                self.onMessage('unread_update', JSON.parse(e.data || '{}'));
            });

            // V2.9.27: 审核通告事件（T-3）
            this.eventSource.addEventListener('audit_approved', function(e) {
                self.lastEventTime = Date.now();
                self._updateLastEventId(e);
                self.onMessage('audit_approved', JSON.parse(e.data || '{}'));
            });
            this.eventSource.addEventListener('audit_rejected', function(e) {
                self.lastEventTime = Date.now();
                self._updateLastEventId(e);
                self.onMessage('audit_rejected', JSON.parse(e.data || '{}'));
            });
            this.eventSource.addEventListener('audit_pending', function(e) {
                self.lastEventTime = Date.now();
                self._updateLastEventId(e);
                self.onMessage('audit_pending', JSON.parse(e.data || '{}'));
            });
            this.eventSource.addEventListener('comment_audit', function(e) {
                self.lastEventTime = Date.now();
                self._updateLastEventId(e);
                self.onMessage('comment_audit', JSON.parse(e.data || '{}'));
            });

            this.eventSource.addEventListener('audit', function(e) {
                self.lastEventTime = Date.now();
                self._updateLastEventId(e);
                self.onMessage('audit', JSON.parse(e.data || '{}'));
            });

            this.eventSource.addEventListener('comment', function(e) {
                self.lastEventTime = Date.now();
                self._updateLastEventId(e);
                self.onMessage('comment', JSON.parse(e.data || '{}'));
            });

            this.eventSource.addEventListener('system', function(e) {
                self.lastEventTime = Date.now();
                self._updateLastEventId(e);
                self.onMessage('system', JSON.parse(e.data || '{}'));
            });

            this.eventSource.addEventListener('system_notice', function(e) {
                self.lastEventTime = Date.now();
                self._updateLastEventId(e);
                self.onMessage('system_notice', JSON.parse(e.data || '{}'));
            });

            this.eventSource.addEventListener('close', function(e) {
                var data = JSON.parse(e.data || '{}');
                // V2.9.27 T-5: 保存Last-Event-Id用于重连补推
                if (data.last_event_id) self.lastEventId = data.last_event_id;
                self.onDisconnect(data);
                self._scheduleReconnect();
            });

            this.eventSource.onerror = function() {
                self.onError({ type: 'eventsource_error' });
                self._scheduleReconnect();
            };
        } catch (err) {
            this.onError({ type: 'init_error', error: err.message });
            this._startPolling();
        }
    };

    // V2.9.27 T-5: 从Server-Sent Events的id字段更新Last-Event-Id
    SseClient.prototype._updateLastEventId = function(e) {
        if (e.lastEventId) {
            this.lastEventId = parseInt(e.lastEventId, 10) || this.lastEventId;
        }
    };

    SseClient.prototype._startHeartbeatCheck = function() {
        var self = this;
        if (this.heartbeatTimer) clearInterval(this.heartbeatTimer);
        this.heartbeatTimer = setInterval(function() {
            if (Date.now() - self.lastHeartbeat > self.heartbeatTimeout) {
                self.onError({ type: 'heartbeat_timeout' });
                self._scheduleReconnect();
            }
        }, 10000);
    };

    SseClient.prototype._scheduleReconnect = function() {
        if (this.destroyed) return;
        this.disconnect();
        var delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), this.maxReconnectDelay);
        this.reconnectAttempts++;
        var self = this;
        this.reconnectTimer = setTimeout(function() { self.connect(); }, delay);
    };

    SseClient.prototype._startPolling = function() {
        if (this.destroyed) return;
        this.pollingMode = true;
        this.disconnect();
        var self = this;
        var poll = function() {
            if (self.destroyed) return;
            $.ajax({
                url: '/api/sse/poll',
                data: { channel: self.channel, last_time: self.lastEventTime },
                timeout: 35000,
                success: function(res) {
                    if (res.code === 0 && res.data) {
                        (res.data.messages || []).forEach(function(msg) {
                            self.onMessage(msg.channel || 'system', msg.payload || {});
                        });
                        self.lastEventTime = res.data.last_time || Math.floor(Date.now() / 1000);
                    }
                },
                error: function() { self.onError({ type: 'poll_error' }); },
                complete: function() {
                    if (!self.destroyed) self.pollingTimer = setTimeout(poll, 5000);
                }
            });
        };
        poll();
    };

    SseClient.prototype.disconnect = function() {
        if (this.eventSource) { this.eventSource.close(); this.eventSource = null; }
        if (this.reconnectTimer) { clearTimeout(this.reconnectTimer); this.reconnectTimer = null; }
        if (this.heartbeatTimer) { clearInterval(this.heartbeatTimer); this.heartbeatTimer = null; }
        if (this.pollingTimer) { clearTimeout(this.pollingTimer); this.pollingTimer = null; }
    };

    SseClient.prototype.destroy = function() { this.destroyed = true; this.disconnect(); };

    global.SseClient = SseClient;
})(window);
