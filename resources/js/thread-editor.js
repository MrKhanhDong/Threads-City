let enginePromise;

export function plainComposerHtml(value) {
    const escaped = String(value).replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
    return escaped ? `<p>${escaped.replace(/\r\n|\r|\n/g, '<br>')}</p>` : '';
}

export function composerText(html, pageDocument = document) {
    const template = pageDocument.createElement('template');
    template.innerHTML = html.replace(/<\/(?:p|div|h[1-6]|li|blockquote|pre)>|<br\s*\/?>/gi, '\n');
    return template.content.textContent.replace(/\u00a0/g, ' ').trim();
}

async function loadEngine(source, pageDocument) {
    const pageWindow = pageDocument.defaultView;
    if (pageWindow.tinymce) return pageWindow.tinymce;
    if (!enginePromise) {
        enginePromise = new Promise((resolve, reject) => {
            const script = pageDocument.createElement('script');
            script.src = source;
            script.async = true;
            script.onload = () => pageWindow.tinymce ? resolve(pageWindow.tinymce) : reject(new Error('Editor unavailable'));
            script.onerror = () => { script.remove(); reject(new Error('Editor unavailable')); };
            pageDocument.head.append(script);
        }).catch(error => { enginePromise = null; throw error; });
    }
    return enginePromise;
}

export async function createThreadEditor(textarea, modalElement, onChange) {
    const pageDocument = textarea.ownerDocument;
    const pageWindow = pageDocument.defaultView;
    const tiny = await loadEngine(modalElement.dataset.editorUrl, pageDocument);
    if (!textarea.isConnected) return null;
    tiny.addI18n('vi', {
        'Paragraph': 'Đoạn văn', 'Heading 1': 'Tiêu đề 1', 'Heading 2': 'Tiêu đề 2', 'Heading 3': 'Tiêu đề 3',
        'Heading 4': 'Tiêu đề 4', 'Bold': 'In đậm', 'Italic': 'In nghiêng', 'Underline': 'Gạch chân',
        'Undo': 'Hoàn tác', 'Redo': 'Làm lại', 'Bullet list': 'Danh sách', 'Numbered list': 'Danh sách đánh số',
        'Blockquote': 'Trích dẫn', 'Remove formatting': 'Xóa định dạng', 'Blocks': 'Định dạng',
        'Align left': 'Căn trái', 'Align center': 'Căn giữa', 'Align right': 'Căn phải',
        'Insert/edit link': 'Chèn/sửa liên kết', 'Insert link': 'Chèn liên kết', 'Link': 'Liên kết',
        'Text to display': 'Chữ hiển thị', 'Title': 'Tiêu đề', 'Save': 'Lưu', 'Cancel': 'Hủy',
        'Open link': 'Mở liên kết', 'Remove link': 'Gỡ liên kết', 'Url': 'Đường dẫn', 'URL': 'Đường dẫn',
        'Clear formatting': 'Xóa định dạng', 'Open link in...': 'Mở liên kết trong…', 'New window': 'Cửa sổ mới',
        'Current window': 'Cửa sổ hiện tại', 'More...': 'Công cụ khác', 'Expand toolbar': 'Mở rộng công cụ',
    });
    const instances = await tiny.init({
        target: textarea,
        license_key: modalElement.dataset.editorLicense || 'gpl',
        base_url: modalElement.dataset.editorUrl.replace(/\/tinymce\.min\.js(?:\?.*)?$/, ''),
        suffix: '.min',
        language: 'vi',
        promotion: false,
        branding: false,
        menubar: false,
        statusbar: false,
        resize: false,
        height: textarea.hasAttribute('data-compose-text') ? 280 : 230,
        plugins: 'lists link autolink',
        toolbar: 'blocks | bold italic underline | bullist numlist blockquote | alignleft aligncenter alignright | link removeformat | undo redo',
        toolbar_mode: 'sliding',
        block_formats: 'Đoạn văn=p;Tiêu đề 1=h1;Tiêu đề 2=h2;Tiêu đề 3=h3;Tiêu đề 4=h4',
        valid_elements: 'p[style],div[style],span[style],h1[style],h2[style],h3[style],h4[style],strong,b,em,i,u,s,br,hr,blockquote,ul,ol[start],li,a[href|title|target|rel],pre,code',
        valid_styles: { '*': 'text-align,color,background-color' },
        paste_data_images: false,
        paste_block_drop: true,
        convert_urls: false,
        link_default_target: '_blank',
        link_assume_external_targets: 'https',
        target_list: false,
        link_context_toolbar: true,
        content_style: 'body{font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:15px;line-height:1.8;margin:12px;overflow-wrap:anywhere}p{margin:0 0 .75em}h1,h2,h3,h4{line-height:1.35;margin:.8em 0 .5em}h1{font-size:1.7em}h2{font-size:1.45em}h3{font-size:1.25em}h4{font-size:1.1em}blockquote{border-left:3px solid var(--editor-accent);margin:1em 0;padding-left:14px}a{color:var(--editor-accent)}',
        setup(editor) {
            const update = () => { editor.save(); onChange(); };
            editor.on('input change undo redo SetContent', update);
            editor.on('init', () => {
                const applyTheme = () => {
                    const colors = pageWindow.getComputedStyle(pageDocument.documentElement);
                    editor.getBody().style.color = colors.getPropertyValue('--journal-text');
                    editor.getBody().style.background = colors.getPropertyValue('--journal-surface');
                    editor.getBody().style.setProperty('--editor-accent', colors.getPropertyValue('--journal-accent'));
                    editor.getDoc().documentElement.style.colorScheme = pageDocument.documentElement.dataset.theme;
                };
                applyTheme();
                const observer = new pageWindow.MutationObserver(applyTheme);
                observer.observe(pageDocument.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
                editor.on('remove', () => observer.disconnect());
                update();
            });
        },
    });
    if (!textarea.isConnected) { instances.forEach(editor => editor.remove()); return null; }
    return instances[0];
}
