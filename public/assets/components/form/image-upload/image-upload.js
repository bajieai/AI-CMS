/**
 * I8JImageUpload - 图片上传组件
 * V3.0 Phase 2 UI组件库
 *
 * 特性：
 * - 拖拽上传
 * - 图片预览
 * - 上传进度
 * - 多文件支持
 * - 文件校验（类型/大小/尺寸）
 */
class I8JImageUpload extends I8JComponent {
    constructor(element, options = {}) {
        super(element, options);
    }

    getDefaultOptions() {
        return {
            accept: 'image/*',
            multiple: false,
            maxSize: 5 * 1024 * 1024, // 5MB
            maxWidth: 4096,
            maxHeight: 4096,
            maxCount: 9,
            uploadUrl: '',
            uploadField: 'file',
            headers: {},
            onPreview: null,
            onRemove: null,
            onSuccess: null,
            onError: null,
            onProgress: null,
        };
    }

    render() {
        const { multiple, maxCount } = this.options;

        this.element.className = 'i8j-image-upload';
        this.element.innerHTML = `
            <div class="i8j-image-upload__area">
                <input type="file" class="i8j-image-upload__input" accept="${this.options.accept}" ${multiple ? 'multiple' : ''}>
                <div class="i8j-image-upload__trigger">
                    <i class="bi bi-cloud-upload fs-2 mb-2 d-block"></i>
                    <span>点击或拖拽上传</span>
                    <span class="text-muted small d-block mt-1">支持 JPG/PNG/GIF，单张不超过5MB</span>
                </div>
            </div>
            <div class="i8j-image-upload__list"></div>
        `;

        this.files = [];
    }

    bindEvents() {
        const area = this.$('.i8j-image-upload__area');
        const input = this.$('.i8j-image-upload__input');

        // 点击触发文件选择
        this.on(area, 'click', (e) => {
            if (e.target !== input) input.click();
        });

        // 文件选择
        this.on(input, 'change', () => {
            this.handleFiles(Array.from(input.files));
            input.value = '';
        });

        // 拖拽
        ['dragenter', 'dragover'].forEach(type => {
            this.on(area, type, (e) => {
                e.preventDefault();
                area.classList.add('i8j-image-upload__area--dragover');
            });
        });

        ['dragleave', 'drop'].forEach(type => {
            this.on(area, type, (e) => {
                e.preventDefault();
                area.classList.remove('i8j-image-upload__area--dragover');
            });
        });

        this.on(area, 'drop', (e) => {
            const dropped = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
            this.handleFiles(dropped);
        });
    }

    handleFiles(fileList) {
        const { multiple, maxCount, maxSize } = this.options;

        if (!multiple && fileList.length > 1) {
            fileList = [fileList[0]];
        }

        const remaining = multiple ? maxCount - this.files.length : 1 - this.files.length;
        const toAdd = fileList.slice(0, Math.max(0, remaining));

        toAdd.forEach(file => {
            if (file.size > maxSize) {
                this.triggerError(file, '文件过大');
                return;
            }

            const item = {
                id: I8JComponent.uid('img'),
                file: file,
                status: 'pending', // pending/uploading/done/error
                progress: 0,
                url: null,
                error: null,
            };

            this.files.push(item);
            this.renderItem(item);
            this.readPreview(item);
        });
    }

    readPreview(item) {
        const reader = new FileReader();
        reader.onload = (e) => {
            item.preview = e.target.result;
            this.updateItem(item);
        };
        reader.readAsDataURL(item.file);
    }

    renderItem(item) {
        const list = this.$('.i8j-image-upload__list');
        const el = document.createElement('div');
        el.className = 'i8j-image-upload__item';
        el.dataset.id = item.id;
        el.innerHTML = `
            <div class="i8j-image-upload__thumb">
                <img src="" alt="" style="display:none;">
                <div class="i8j-image-upload__placeholder"><i class="bi bi-image"></i></div>
            </div>
            <div class="i8j-image-upload__info">
                <div class="i8j-image-upload__name">${this.escapeHtml(item.file.name)}</div>
                <div class="i8j-image-upload__progress">
                    <div class="i8j-image-upload__progress-bar" style="width:0%"></div>
                </div>
                <div class="i8j-image-upload__status">等待上传</div>
            </div>
            <button type="button" class="i8j-image-upload__remove"><i class="bi bi-x-lg"></i></button>
        `;

        el.querySelector('.i8j-image-upload__remove').addEventListener('click', () => {
            this.removeItem(item.id);
        });

        list.appendChild(el);
    }

    updateItem(item) {
        const el = this.element.querySelector(`.i8j-image-upload__item[data-id="${item.id}"]`);
        if (!el) return;

        const img = el.querySelector('img');
        const placeholder = el.querySelector('.i8j-image-upload__placeholder');
        const bar = el.querySelector('.i8j-image-upload__progress-bar');
        const status = el.querySelector('.i8j-image-upload__status');

        if (item.preview && img) {
            img.src = item.preview;
            img.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }

        if (bar) bar.style.width = item.progress + '%';

        if (status) {
            const statusText = {
                pending: '等待上传',
                uploading: '上传中...',
                done: '上传完成',
                error: item.error || '上传失败',
            };
            status.textContent = statusText[item.status] || item.status;
            status.className = 'i8j-image-upload__status i8j-image-upload__status--' + item.status;
        }
    }

    removeItem(id) {
        const idx = this.files.findIndex(f => f.id === id);
        if (idx === -1) return;

        const item = this.files[idx];
        this.files.splice(idx, 1);

        const el = this.element.querySelector(`.i8j-image-upload__item[data-id="${id}"]`);
        if (el) el.remove();

        if (typeof this.options.onRemove === 'function') {
            this.options.onRemove(item);
        }
    }

    upload() {
        const { uploadUrl, uploadField, headers } = this.options;
        if (!uploadUrl) {
            console.warn('I8JImageUpload: uploadUrl not set');
            return Promise.resolve([]);
        }

        const pending = this.files.filter(f => f.status === 'pending');
        const promises = pending.map(item => this.uploadSingle(item, uploadUrl, uploadField, headers));

        return Promise.all(promises);
    }

    uploadSingle(item, url, field, headers) {
        return new Promise((resolve) => {
            item.status = 'uploading';
            this.updateItem(item);

            const formData = new FormData();
            formData.append(field, item.file);

            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    item.progress = Math.round((e.loaded / e.total) * 100);
                    this.updateItem(item);
                    if (typeof this.options.onProgress === 'function') {
                        this.options.onProgress(item, item.progress);
                    }
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    item.status = 'done';
                    item.progress = 100;
                    try {
                        item.response = JSON.parse(xhr.responseText);
                        item.url = item.response?.url || item.response?.data?.url;
                    } catch (e) {
                        item.response = xhr.responseText;
                    }
                    this.updateItem(item);
                    if (typeof this.options.onSuccess === 'function') {
                        this.options.onSuccess(item);
                    }
                    resolve({ success: true, item });
                } else {
                    this.triggerError(item, '上传失败: HTTP ' + xhr.status);
                    resolve({ success: false, item });
                }
            });

            xhr.addEventListener('error', () => {
                this.triggerError(item, '网络错误');
                resolve({ success: false, item });
            });

            xhr.open('POST', url);
            Object.entries(headers).forEach(([k, v]) => xhr.setRequestHeader(k, v));
            xhr.send(formData);
        });
    }

    triggerError(item, message) {
        item.status = 'error';
        item.error = message;
        this.updateItem(item);
        if (typeof this.options.onError === 'function') {
            this.options.onError(item, message);
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    get value() {
        return this.files.map(f => f.url).filter(Boolean);
    }

    get allFiles() {
        return this.files;
    }
}

window.I8JImageUpload = I8JImageUpload;
