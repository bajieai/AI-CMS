/**
 * 评价媒体上传组件 - V2.9.1 M15a
 * 复用 /api/upload/image 接口
 *
 * 用法:
 *   const uploader = new RatingMediaUploader({
 *     container: '#mediaUploader',
 *     inputName: 'media_urls',
 *     maxFiles: 5,
 *     accept: 'image/*',
 *   });
 *
 * 功能:
 *   - 多图选择/拖拽上传
 *   - 实时预览（缩略图）
 *   - 上传进度条
 *   - 删除已选图片
 *   - 自动收集URL传入隐藏input
 */
class RatingMediaUploader {
    constructor(options) {
        this.container = document.querySelector(options.container);
        if (!this.container) {
            console.error('[RatingMediaUploader] 容器不存在:', options.container);
            return;
        }

        this.maxFiles = options.maxFiles || 5;
        this.accept = options.accept || 'image/*';
        this.inputName = options.inputName || 'media_urls';
        this.csrfToken = options.csrfToken || '';
        this.files = []; // {file, url, status, progress}

        this.initDom();
        this.bindEvents();
    }

    initDom() {
        this.container.innerHTML = `
            <div class="rmu-dropzone" style="border:2px dashed #ccc;border-radius:8px;padding:20px;text-align:center;cursor:pointer;transition:border-color .2s;">
                <i class="bi bi-images fs-2 text-muted"></i>
                <p class="text-muted small mb-1">点击选择图片或拖拽到此处</p>
                <p class="text-muted small mb-0">最多 ${this.maxFiles} 张，支持 JPG/PNG/GIF</p>
                <input type="file" class="rmu-input" accept="${this.accept}" multiple style="display:none;">
            </div>
            <div class="rmu-preview mt-2" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
            <input type="hidden" name="${this.inputName}" class="rmu-hidden" value="">
        `;

        this.dropzone = this.container.querySelector('.rmu-dropzone');
        this.fileInput = this.container.querySelector('.rmu-input');
        this.previewContainer = this.container.querySelector('.rmu-preview');
        this.hiddenInput = this.container.querySelector('.rmu-hidden');
    }

    bindEvents() {
        const self = this;

        this.dropzone.addEventListener('click', () => this.fileInput.click());

        this.fileInput.addEventListener('change', function () {
            self.addFiles(this.files);
        });

        // 拖拽事件
        this.dropzone.addEventListener('dragover', function (e) {
            e.preventDefault();
            this.style.borderColor = '#3b82f6';
            this.style.background = '#eff6ff';
        });
        this.dropzone.addEventListener('dragleave', function (e) {
            e.preventDefault();
            this.style.borderColor = '#ccc';
            this.style.background = '';
        });
        this.dropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            this.style.borderColor = '#ccc';
            this.style.background = '';
            self.addFiles(e.dataTransfer.files);
        });
    }

    addFiles(fileList) {
        const remaining = this.maxFiles - this.files.length;
        if (remaining <= 0) {
            alert('最多只能上传 ' + this.maxFiles + ' 张图片');
            return;
        }

        const toAdd = Array.from(fileList).slice(0, remaining);
        toAdd.forEach(file => {
            if (!file.type.startsWith('image/')) return;
            const item = {
                file: file,
                url: '',
                status: 'uploading',
                progress: 0,
                id: 'rmu_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
            };
            this.files.push(item);
            this.renderItem(item);
            this.uploadFile(item);
        });
    }

    renderItem(item) {
        const div = document.createElement('div');
        div.className = 'rmu-item';
        div.id = item.id;
        div.style.cssText = 'position:relative;width:80px;height:80px;border-radius:6px;overflow:hidden;border:1px solid #e2e8f0;';

        const objectUrl = URL.createObjectURL(item.file);
        div.innerHTML = `
            <img src="${objectUrl}" style="width:100%;height:100%;object-fit:cover;">
            <div class="rmu-progress" style="position:absolute;bottom:0;left:0;height:3px;background:#3b82f6;width:0%;transition:width .2s;"></div>
            <button type="button" class="rmu-remove" style="position:absolute;top:2px;right:2px;width:20px;height:20px;border:none;border-radius:50%;background:rgba(0,0,0,.5);color:#fff;font-size:12px;cursor:pointer;line-height:20px;text-align:center;padding:0;">&times;</button>
        `;

        const self = this;
        div.querySelector('.rmu-remove').addEventListener('click', function (e) {
            e.stopPropagation();
            self.removeFile(item.id);
        });

        this.previewContainer.appendChild(div);
    }

    updateProgress(id, percent) {
        const item = this.container.querySelector('#' + id);
        if (item) {
            const bar = item.querySelector('.rmu-progress');
            if (bar) bar.style.width = percent + '%';
        }
    }

    setItemDone(id, url) {
        const item = this.container.querySelector('#' + id);
        if (item) {
            item.querySelector('.rmu-progress').style.width = '100%';
            item.querySelector('.rmu-progress').style.background = '#22c55e';
        }
        const fileItem = this.files.find(f => f.id === id);
        if (fileItem) {
            fileItem.status = 'done';
            fileItem.url = url;
        }
        this.syncHiddenInput();
    }

    setItemError(id) {
        const item = this.container.querySelector('#' + id);
        if (item) {
            item.querySelector('.rmu-progress').style.width = '100%';
            item.querySelector('.rmu-progress').style.background = '#ef4444';
        }
        const fileItem = this.files.find(f => f.id === id);
        if (fileItem) fileItem.status = 'error';
    }

    removeFile(id) {
        const idx = this.files.findIndex(f => f.id === id);
        if (idx > -1) {
            this.files.splice(idx, 1);
        }
        const el = this.container.querySelector('#' + id);
        if (el) el.remove();
        this.syncHiddenInput();
    }

    syncHiddenInput() {
        const urls = this.files.filter(f => f.status === 'done' && f.url).map(f => f.url);
        this.hiddenInput.value = JSON.stringify(urls);
    }

    uploadFile(item) {
        const self = this;
        const formData = new FormData();
        formData.append('file', item.file);
        if (this.csrfToken) {
            formData.append('_token', this.csrfToken);
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/upload/image', true);

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                self.updateProgress(item.id, percent);
            }
        };

        xhr.onload = function () {
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.code === 0 && res.data && res.data.url) {
                    self.setItemDone(item.id, res.data.url);
                } else {
                    self.setItemError(item.id);
                }
            } catch (e) {
                self.setItemError(item.id);
            }
        };

        xhr.onerror = function () {
            self.setItemError(item.id);
        };

        xhr.send(formData);
    }

    getUrls() {
        return this.files.filter(f => f.status === 'done' && f.url).map(f => f.url);
    }

    reset() {
        this.files = [];
        this.previewContainer.innerHTML = '';
        this.hiddenInput.value = '';
    }
}

if (typeof window !== 'undefined') {
    window.RatingMediaUploader = RatingMediaUploader;
}
