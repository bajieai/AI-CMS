/**
 * V2.9.20 C-1: SSE 客户端
 * 
 * 特性：
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
        this.maxReconnectDelay = 60000; // 最大60秒
        this.heartbeatTimeout = 60000;  // 60秒未收到心跳则重连
        this.heartbeatTimer = null;
        this.lastHeartbeat = 0;
        this.pollingMode = false;
        this.pollingTimer = null;
        this.lastEventTime = 0;
        this.destroyed = false;
    }

    SseClient.prototype.connect = function() {
        if (this.destroyed) return;
        this.disconnect();

        // 优先使用 EventSource
        if (typeof EventSource !== 'undefined') {
            this._connectEventSource();
        } else {
            // 降级到长轮询
            this._startPolling();
        }
    };

    SseClient.prototype._connectEventSource = function() {
        var self = this;
        var url = this.url + (this.url.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();

        try {
            this.eventSource = new EventSource(url);
            this.lastHeartbeat = Date.now();
            this.lastEventTime = Date.now();

            this.eventSource.addEventListener('connected', function(e) {
                self.reconnectAttempts = 0;
                self.onConnect(JSON.parse(e.data || '{}'));
                self._startHeartbeatCheck();
            });

            this.eventSource.addEventListener('heartbeat', function(e) {
                self.lastHeartbeat = Date.now();
            });

            this.eventSource.addEventListener('audit', function(e) {
                self.lastEventTime = Date.now();
                self.onMessage('audit', JSON.parse(e.data || '{}'));
            });

            this.eventSource.addEventListener('comment', function(e) {
                self.lastEventTime = Date.now();
                self.onMessage('comment', JSON.parse(e.data || '{}'));
            });

            this.eventSource.addEventListener('system', function(e) {
                self.lastEventTime = Date.now();
                self.onMessage('system', JSON.parse(e.data || '{}'));
            });

            this.eventSource.addEventListener('close', function(e) {
                self.onDisconnect(JSON.parse(e.data || '{}'));
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

    SseClient.prototype._startHeartbeatCheck = function() {
        var self = this;
        if (this.heartbeatTimer) clearInterval(this.heartbeatTimer);
        this.heartbeatTimer = setInterval(function() {
            if (Date.now() - self.lastHeartbeat > self.heartbeatTimeout) {
                self.onError({ type: 'heartbeat_timeout' });
                self._scheduleReconnect();
            }
        }, 10000); // 每10秒检查一次
    };

    SseClient.prototype._scheduleReconnect = function() {
        if (this.destroyed) return;
        this.disconnect();

        var delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), this.maxReconnectDelay);
        this.reconnectAttempts++;

        var self = this;
        this.reconnectTimer = setTimeout(function() {
            self.connect();
        }, delay);
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
                error: function() {
                    self.onError({ type: 'poll_error' });
                },
                complete: function() {
                    if (!self.destroyed) {
                        self.pollingTimer = setTimeout(poll, 5000);
                    }
                }
            });
        };
        poll();
    };

    SseClient.prototype.disconnect = function() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
        if (this.pollingTimer) {
            clearTimeout(this.pollingTimer);
            this.pollingTimer = null;
        }
    };

    SseClient.prototype.destroy = function() {
        this.destroyed = true;
        this.disconnect();
    };

    // 全局暴露
    global.SseClient = SseClient;
})(window);
