export async function prepareThreadImage(file, env = globalThis) {
    if (!['image/jpeg', 'image/png'].includes(file.type) || file.size <= 256 * 1024) return file;
    const previewUrl = env.URL.createObjectURL(file);
    try {
        const image = await new Promise((resolve, reject) => {
            const image = new env.Image();
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = previewUrl;
        });
        const scale = Math.min(1, 1920 / Math.max(image.naturalWidth, image.naturalHeight));
        const canvas = env.document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', 0.82));
        if (!blob || blob.size >= file.size || blob.type !== 'image/webp') return file;
        return new env.File([blob], file.name.replace(/\.[^.]+$/, '') + '.webp', { type: blob.type, lastModified: file.lastModified });
    } catch {
        return file;
    } finally {
        env.URL.revokeObjectURL(previewUrl);
    }
}

export async function uploadThreadImage(file, url, csrf, { signal, onProgress = () => {}, onStage = () => {}, env = globalThis } = {}) {
    if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type)) throw new Error('Chọn ảnh JPEG, PNG, WebP hoặc GIF.');
    if (file.size > 5 * 1024 * 1024) throw new Error('Ảnh vượt quá giới hạn 5 MB.');
    if (signal?.aborted) throw new DOMException('Đã hủy tải ảnh.', 'AbortError');
    onStage('preparing');
    const prepared = await prepareThreadImage(file, env);
    if (signal?.aborted) throw new DOMException('Đã hủy tải ảnh.', 'AbortError');

    return new Promise((resolve, reject) => {
        const xhr = new env.XMLHttpRequest();
        const abort = () => xhr.abort();
        const finish = callback => {
            signal?.removeEventListener('abort', abort);
            callback();
        };
        xhr.open('POST', url);
        xhr.timeout = 45000;
        xhr.responseType = 'json';
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        xhr.upload.onprogress = event => {
            if (event.lengthComputable && event.total > 0) onProgress(Math.min(100, Math.round(event.loaded / event.total * 100)));
        };
        xhr.upload.onload = () => onStage('saving');
        xhr.onload = () => finish(() => {
            const data = xhr.response;
            if (xhr.status < 200 || xhr.status >= 300) {
                const message = Object.values(data?.errors || {}).flat()[0]
                    || (xhr.status === 419 ? 'Phiên đăng nhập đã hết hạn. Hãy tải lại trang rồi thử lại.' : data?.message)
                    || 'Không tải được ảnh. Hãy thử lại.';
                reject(new Error(message));
                return;
            }
            if (!Number.isInteger(data?.id) || typeof data?.url !== 'string' || !data.url) {
                reject(new Error('Máy chủ chưa trả về thông tin ảnh hợp lệ. Hãy thử lại.'));
                return;
            }
            resolve(data);
        });
        xhr.onerror = () => finish(() => reject(new Error('Không kết nối được máy chủ. Hãy kiểm tra mạng và thử lại.')));
        xhr.ontimeout = () => finish(() => reject(new Error('Tải ảnh quá lâu. Hãy thử lại với ảnh nhỏ hơn hoặc kiểm tra kết nối.')));
        xhr.onabort = () => finish(() => reject(new DOMException('Đã hủy tải ảnh.', 'AbortError')));
        signal?.addEventListener('abort', abort, { once: true });
        const body = new env.FormData();
        body.append('image', prepared);
        onStage('uploading');
        try { xhr.send(body); }
        catch { finish(() => reject(new Error('Không gửi được ảnh. Hãy kiểm tra kết nối rồi thử lại.'))); }
    });
}
