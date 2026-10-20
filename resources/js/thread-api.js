export function composerValidation(content, mediaCount, continuations = []) {
    if (Array.from(content.trim()).length > 500) return 'Mỗi bài viết tối đa 500 ký tự.';
    if (mediaCount > 4) return 'Mỗi bài viết tối đa 4 ảnh.';
    if (!content.trim() && !mediaCount) return 'Nhập nội dung hoặc đính kèm ít nhất một ảnh.';
    if (continuations.length > 9) return 'Một chuỗi có tối đa 10 bài viết.';
    if (continuations.some(text => !text.trim())) return 'Nhập nội dung cho từng bài trong chuỗi.';
    if (continuations.some(text => Array.from(text.trim()).length > 500)) return 'Mỗi bài trong chuỗi tối đa 500 ký tự.';
    return '';
}

export async function threadRequest(url, { method = 'GET', body, csrf = '', fetcher = globalThis.fetch } = {}) {
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf };
    let payload = body;
    if (body && !(body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }
    let response;
    try { response = await fetcher(url, { method, headers, body: payload, credentials: 'same-origin' }); }
    catch { throw new Error('Không kết nối được máy chủ. Nội dung của bạn vẫn được giữ lại.'); }
    let data;
    try { data = await response.json(); }
    catch { throw new Error('Phiên làm việc có thể đã hết hạn. Hãy lưu nội dung và tải lại trang.'); }
    if (!response.ok) {
        const messages = Object.values(data.errors ?? {}).flat();
        const error = new Error(messages[0] || (response.status === 419 ? 'Phiên đã hết hạn. Nội dung vẫn được giữ lại; hãy tải lại trang rồi thử lại.' : data.message) || 'Chưa hoàn tất thao tác. Vui lòng thử lại.');
        error.status = response.status;
        throw error;
    }
    if (!data || typeof data !== 'object') throw new Error('Phản hồi không hợp lệ. Hãy thử lại.');
    return data;
}
