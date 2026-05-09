/**
 * 配图异步轮询组件 - V2.9.1 M14a
 * 用法:
 *   const poll = new ImagePoll({ taskId: 'flux_xxx', onComplete: (url) => { ... } });
 *   poll.start();
 *
 * 功能:
 *   - 自动轮询 /api/image/status 接口
 *   - 支持4种状态: pending/processing/completed/failed
 *   - 实时进度条 (progress 0-100)
 *   - 失败自动重试提示
 *   - 支持批量轮询多个任务
 */
class ImagePoll {
    constructor(options) {
        this.taskId = options.taskId || '';
        this.interval = options.interval || 3000; // 默认3秒轮询一次
        this.maxAttempts = options.maxAttempts || 40; // 前端最多轮询40次(120秒)
        this.onComplete = options.onComplete || (() => {});
        this.onFailed = options.onFailed || (() => {});
        this.onProgress = options.onProgress || (() => {});
        this.onError = options.onError || (() => {});

        this.attempts = 0;
        this.timer = null;
        this.aborted = false;
    }

    /**
     * 开始轮询
     */
    start() {
        if (!this.taskId) {
            console.error('[ImagePoll] 缺少taskId');
            return;
        }
        this.aborted = false;
        this.attempts = 0;
        this._poll();
    }

    /**
     * 停止轮询
     */
    stop() {
        this.aborted = true;
        if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
        }
    }

    /**
     * 单次轮询
     */
    _poll() {
        if (this.aborted) return;
        this.attempts++;

        fetch('/api/image/status?task_id=' + encodeURIComponent(this.taskId))
            .then(r => r.json())
            .then(res => {
                if (res.code !== 0) {
                    this.onError(res.msg || '查询失败');
                    this._scheduleNext();
                    return;
                }

                const data = res.data;
                const status = data.status;
                const progress = data.progress;

                this.onProgress({
                    status: status,
                    progress: progress,
                    attempts: data.attempts,
                    maxAttempts: data.max_attempts,
                    retryCount: data.retry_count,
                });

                if (status === 'completed') {
                    this.stop();
                    this.onComplete({
                        imageUrl: data.image_url,
                        localPath: data.local_path,
                        taskId: data.task_id,
                    });
                    return;
                }

                if (status === 'failed') {
                    this.stop();
                    this.onFailed({
                        errorMsg: data.error_msg,
                        retryCount: data.retry_count,
                        taskId: data.task_id,
                    });
                    return;
                }

                // pending / processing: 继续轮询
                if (this.attempts >= this.maxAttempts) {
                    this.stop();
                    this.onFailed({ errorMsg: '前端轮询超时', taskId: this.taskId });
                    return;
                }

                this._scheduleNext();
            })
            .catch(err => {
                this.onError(err.message);
                this._scheduleNext();
            });
    }

    _scheduleNext() {
        if (this.aborted) return;
        this.timer = setTimeout(() => this._poll(), this.interval);
    }
}

/**
 * 批量配图轮询管理器
 * 用法:
 *   const batch = new ImagePollBatch();
 *   batch.addTask('flux_xxx', { onComplete: ... });
 *   batch.addTask('flux_yyy', { onComplete: ... });
 */
class ImagePollBatch {
    constructor() {
        this.pollers = {};
    }

    addTask(taskId, callbacks) {
        if (this.pollers[taskId]) {
            this.pollers[taskId].stop();
        }
        this.pollers[taskId] = new ImagePoll({
            taskId: taskId,
            ...callbacks,
        });
        this.pollers[taskId].start();
        return this.pollers[taskId];
    }

    removeTask(taskId) {
        if (this.pollers[taskId]) {
            this.pollers[taskId].stop();
            delete this.pollers[taskId];
        }
    }

    stopAll() {
        Object.values(this.pollers).forEach(p => p.stop());
        this.pollers = {};
    }

    getCompletedCount() {
        // 由调用方维护计数
        return 0;
    }
}

// 兼容CommonJS/ESM/浏览器全局
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ImagePoll, ImagePollBatch };
}
if (typeof window !== 'undefined') {
    window.ImagePoll = ImagePoll;
    window.ImagePollBatch = ImagePollBatch;
}
