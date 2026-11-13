export function setupAvatarPreview(doc = document, win = window) {
    const input = doc.querySelector('[data-avatar-input]');
    const form = input?.closest('form');
    const preview = form?.querySelector('[data-avatar-preview]');
    const status = form?.querySelector('[data-avatar-status]');
    if (!input || !preview || !status) return;

    const savedAvatar = [...preview.childNodes];
    let previewUrl = null;
    const message = (text = '', error = false) => {
        status.textContent = text;
        status.classList.toggle('text-danger', error);
        status.classList.toggle('text-secondary', !error);
    };
    const restore = () => {
        if (previewUrl) win.URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        preview.replaceChildren(...savedAvatar);
        message();
    };

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        restore();
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            input.value = '';
            message('Hãy chọn ảnh JPEG, PNG hoặc WebP, dung lượng tối đa 2 MB.', true);
            return;
        }

        const image = doc.createElement('img');
        const source = win.URL.createObjectURL(file);
        previewUrl = source;
        image.alt = 'Ảnh đại diện xem trước';
        image.onerror = () => {
            if (previewUrl !== source) return;
            restore();
            input.value = '';
            message('Không đọc được ảnh này. Hãy chọn một ảnh khác.', true);
        };
        image.src = source;
        preview.replaceChildren(image);
        message(`Đã chọn ${file.name}. Bấm “Lưu thay đổi” để cập nhật ảnh đại diện.`);
    });
    form.addEventListener('reset', restore);
}
