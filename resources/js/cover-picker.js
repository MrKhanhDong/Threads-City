import { requestErrorMessage } from './validation-messages.js';

export async function uploadCover(file, url, csrf, alt = '') {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) throw new Error('Hãy chọn ảnh JPEG, PNG hoặc WebP.');
    if (file.size > 5 * 1024 * 1024) throw new Error('Ảnh vượt quá giới hạn 5 MB.');
    const body = new FormData();
    body.append('file', file);
    body.append('alt', alt);
    const response = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
    const data = await response.json();
    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Chưa tải được ảnh. Vui lòng thử lại.');
    if (!data.id || typeof data.location !== 'string') throw new Error('Máy chủ chưa trả về thông tin ảnh hợp lệ.');
    return { id: String(data.id), url: data.location, name: file.name, alt: data.alt || '' };
}

export function setupCoverPicker(form, csrf) {
    const modal = document.querySelector('[data-cover-picker]');
    if (!modal) return;
    const cover = form.elements.namedItem('cover_media_id');
    const preview = form.querySelector('[data-cover-preview]');
    const remove = form.querySelector('[data-cover-remove]');
    const grid = modal.querySelector('[data-cover-library]');
    const search = modal.querySelector('[data-cover-search]');
    const fileInput = modal.querySelector('[data-cover-file]');
    const upload = modal.querySelector('[data-cover-upload]');
    const confirm = modal.querySelector('[data-cover-confirm]');
    const status = modal.querySelector('[data-cover-status]');
    let selected = null, uploading = false;

    const message = (text, error = false) => {
        status.textContent = text;
        status.classList.toggle('text-danger', error);
    };
    const refreshPreview = () => {
        preview.hidden = !cover.value;
        remove.hidden = !cover.value;
        if (cover.value) preview.src = cover.dataset.previewUrl;
        else preview.removeAttribute('src');
    };
    const filter = () => {
        const query = search.value.trim().toLocaleLowerCase('vi');
        let count = 0;
        grid.querySelectorAll('[data-cover-item]').forEach(button => {
            button.hidden = !button.dataset.name.toLocaleLowerCase('vi').includes(query);
            if (!button.hidden) count += 1;
        });
        modal.querySelector('[data-cover-empty]').hidden = count > 0;
    };
    const select = image => {
        selected = image;
        confirm.disabled = uploading || !image;
        grid.querySelectorAll('[data-cover-item]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.id === image?.id));
        });
        modal.querySelector('[data-cover-selection]').textContent = image ? 'Đã chọn: ' + image.name : 'Chưa chọn ảnh.';
    };
    grid.addEventListener('click', event => {
        const button = event.target.closest('[data-cover-item]');
        if (button && !uploading) select({ ...button.dataset });
    });
    modal.addEventListener('show.bs.modal', () => {
        if (uploading) return;
        const current = [...grid.querySelectorAll('[data-cover-item]')].find(button => button.dataset.id === cover.value);
        select(current ? { ...current.dataset } : null);
        message('');
        search.value = '';
        filter();
    });
    search.addEventListener('input', filter);
    confirm.addEventListener('click', () => {
        if (!selected || uploading) return;
        cover.value = selected.id;
        cover.dataset.previewUrl = selected.url;
        preview.alt = selected.alt || 'Ảnh bìa đã chọn';
        refreshPreview();
        cover.dispatchEvent(new Event('change', { bubbles: true }));
    });
    remove.addEventListener('click', () => {
        cover.value = '';
        cover.dataset.previewUrl = '';
        refreshPreview();
        cover.dispatchEvent(new Event('change', { bubbles: true }));
    });
    upload.addEventListener('click', async () => {
        const file = fileInput.files[0];
        if (!file || uploading) { if (!file) message('Hãy chọn ảnh từ máy trước.', true); return; }
        uploading = true;
        upload.disabled = fileInput.disabled = confirm.disabled = true;
        modal.setAttribute('aria-busy', 'true');
        message('Đang tải ảnh lên thư viện…');
        try {
            const image = await uploadCover(file, modal.dataset.uploadUrl, csrf, modal.querySelector('[data-cover-alt]').value);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'cover-library-item';
            button.dataset.coverItem = '';
            Object.assign(button.dataset, image);
            const thumbnail = document.createElement('img');
            thumbnail.src = image.url;
            thumbnail.alt = image.alt;
            thumbnail.loading = 'lazy';
            const name = document.createElement('span');
            name.textContent = image.name;
            button.append(thumbnail, name);
            grid.prepend(button);
            const option = new Option(image.name, image.id);
            option.dataset.previewUrl = image.url;
            form.elements.namedItem('og_media_id')?.add(option);
            search.value = '';
            filter();
            select(image);
            fileInput.value = '';
            modal.querySelector('[data-cover-alt]').value = '';
            modal.querySelector('[data-cover-library-tab]').click();
            message('Đã lưu ảnh vào thư viện. Bấm “Dùng làm ảnh bìa” để chọn.');
        } catch (error) {
            message(requestErrorMessage(error, 'Không tải được ảnh. Kiểm tra kết nối rồi thử lại.'), true);
        } finally {
            uploading = false;
            upload.disabled = fileInput.disabled = false;
            confirm.disabled = !selected;
            modal.setAttribute('aria-busy', 'false');
        }
    });
    modal.addEventListener('hide.bs.modal', event => {
        if (uploading) { event.preventDefault(); message('Vui lòng chờ ảnh tải lên xong.'); }
    });
    window.addEventListener('beforeunload', event => {
        if (uploading) { event.preventDefault(); event.returnValue = ''; }
    });
    refreshPreview();
}
