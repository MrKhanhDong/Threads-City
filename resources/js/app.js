import { Dropdown, Offcanvas, Modal } from 'bootstrap';
import { setupJournalTheme } from './journal-theme';
import { setupChat } from './chat.js';
import { setupDesktopColumns } from './desktop-columns';
import { setupSidebarFeeds } from './sidebar-feeds';
import { setupSidebarDrawer } from './sidebar-drawer';
import { setupPhuongComposerBridge } from './phuong-interface';
import { setupLiveUpdates } from './live-updates';
import { setupThreadMedia } from './thread-media';
import { setupVoiceRecorder } from './voice-recorder';
import { setupPolls } from './polls';
import { setupCommentThreads, setupReplyNotifications } from './comment-threads';
import { setupSeoPreview } from './seo-preview';
import { setupCoverPicker } from './cover-picker';
import { setupSeoChecks } from './seo-checks';
import { setupLightbox, setupConnections, setupPullToRefresh, setupFeedPagination } from './mobile-experience.js';
import { setupInsights } from './insights.js';
import { setupThreadStream } from './thread-stream.js';
import { setupCommentMentions } from './mentions.js';
import { setupPwa } from './pwa.js';
import { setupLocationGps } from './location-picker.js';
import { uploadThreadImage } from './image-upload.js';
import { setupVietnameseValidation, requestErrorMessage } from './validation-messages';

window.bootstrap = { ...window.bootstrap, Dropdown, Offcanvas, Modal };
window.uploadThreadImage = uploadThreadImage;

const showState = (element, text, error = false) => {
    if (!element) return;
    element.textContent = text;
    element.classList.toggle('error', error);
};

async function copyText(value, button) {
    try {
        await navigator.clipboard.writeText(value);
        const original = button.textContent;
        button.textContent = 'Đã sao chép ✓';
        setTimeout(() => { button.textContent = original; }, 2500);
    } catch {
        window.prompt('Sao chép liên kết này:', value);
    }
}

function setupEditor() {
    const form = document.querySelector('[data-editor-form]');
    if (!form) return;
    const textarea = form.querySelector('[data-editor]');
    const state = document.getElementById('editor-save-state');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    setupCoverPicker(form, csrf);
    let editor = null, revision = 0, dirty = false, submitting = false, timer = null, autosaveTask = null;
    const updateSeoChecks = setupSeoChecks(form, csrf, () => editor?.initialized ? editor.getContent() : textarea.value);

    const markDirty = () => {
        revision += 1;
        dirty = true;
        showState(state, 'Có thay đổi chưa lưu.');
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (!submitting && !autosaveTask && dirty && form.dataset.autosaveUrl && form.querySelector('[data-autosave-enabled]')?.checked && form.elements.status.value === 'draft') {
                autosaveTask = autosave().finally(() => { autosaveTask = null; });
            }
        }, 45000);
    };
    form.addEventListener('input', event => { if (event.target.name && event.target.name !== 'lock_version') markDirty(); });
    form.addEventListener('change', event => { if (event.target.name && event.target.name !== 'lock_version') markDirty(); });
    window.addEventListener('beforeunload', event => {
        if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
    });

    const imageUpload = (blobInfo, progress) => new Promise((resolve, reject) => {
        if (blobInfo.blob().size > 5 * 1024 * 1024) { reject('Ảnh vượt quá giới hạn 5 MB.'); return; }
        const xhr = new XMLHttpRequest();
        xhr.open('POST', textarea.dataset.uploadUrl);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.onprogress = event => { if (event.lengthComputable) progress(event.loaded / event.total * 100); };
        xhr.onerror = () => reject('Không tải được ảnh. Kiểm tra kết nối và thử lại.');
        xhr.onload = () => {
            let data;
            try { data = JSON.parse(xhr.responseText); } catch { reject('Phiên đăng nhập hoặc phản hồi upload không hợp lệ.'); return; }
            if (xhr.status < 200 || xhr.status >= 300) { reject(Object.values(data.errors || {}).flat().join(' ') || 'Upload bị từ chối. Đăng nhập lại nếu phiên đã hết hạn.'); return; }
            if (typeof data.location !== 'string') { reject('Server chưa trả về URL ảnh.'); return; }
            resolve(data.location);
        };
        const body = new FormData();
        body.append('file', blobInfo.blob(), blobInfo.filename());
        xhr.send(body);
    });

    if (window.tinymce) {
        window.tinymce.init({
            target: textarea,
            license_key: textarea.dataset.license || 'gpl',
            base_url: '/vendor/tinymce',
            suffix: '.min',
            height: 650,
            promotion: false,
            branding: false,
            menubar: 'file edit view insert format tools table help',
            plugins: 'advlist autolink lists link image table searchreplace wordcount code fullscreen preview visualblocks help',
            toolbar: [
                'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor',
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table blockquote hr | searchreplace removeformat | code fullscreen preview'
            ],
            toolbar_mode: 'wrap',
            block_formats: 'Paragraph=p;Heading 1=h1;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6',
            font_family_formats: 'Lora=Threads Lora,Moc Lora,Times New Roman,serif;Arial=arial,helvetica,sans-serif;Times New Roman=times new roman,times,serif;Verdana=verdana,geneva,sans-serif',
            font_size_formats: '10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 48px',
            content_css: '/fonts/lora/fonts.css',
            content_style: 'body{font-family:"Threads Lora","Moc Lora","Times New Roman",serif;font-size:17px;color:#252823;line-height:1.8;padding:15px}h1,h2,h3{color:#24332d}img{max-width:100%;height:auto}table{border-collapse:collapse;width:100%}td,th{border:1px solid #d8dccf;padding:10px}blockquote{border-left:2px solid #c2a878;padding-left:20px;color:#52634b}figcaption{font-size:12px;color:#686c64;text-align:center}',
            image_title: true,
            image_caption: true,
            image_advtab: true,
            automatic_uploads: true,
            paste_data_images: true,
            images_file_types: 'jpg,jpeg,png,webp',
            images_upload_handler: imageUpload,
            setup(instance) {
                editor = instance;
                instance.on('change input undo redo', markDirty);
                instance.on('change input undo redo SetContent NodeChange ObjectResized init', updateSeoChecks);
                instance.on('init', () => showState(state, 'Editor sẵn sàng. Ảnh được tải lên server khi chèn.'));
            }
        }).catch(() => showState(state, 'Chưa tải được editor. Nội dung vẫn còn trong textarea; kiểm tra npm run build trước khi biên tập.', true));
    } else {
        showState(state, 'Không tải được TinyMCE. Nội dung vẫn còn trong textarea; cần build assets trước khi dùng editor.', true);
    }

    async function prepareContent() {
        if (editor?.initialized) {
            const uploads = await editor.uploadImages();
            if (uploads.some(upload => !upload.status) || editor.getBody().querySelector('img[src^="blob:"],img[src^="data:"]')) {
                throw new Error('Có ảnh chưa tải lên thành công. Hãy thử lại trước khi lưu bài.');
            }
            editor.save();
        }
    }

    async function autosave() {
        if (!form.checkValidity()) { showState(state, 'Chưa tự lưu: hãy nhập tiêu đề và dữ liệu hợp lệ.', true); return; }
        try {
            await prepareContent();
            const savedRevision = revision;
            const body = new FormData(form);
            body.delete('_method');
            showState(state, 'Đang tự lưu bản nháp...');
            const response = await fetch(form.dataset.autosaveUrl, { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
            const data = await response.json();
            if (!response.ok) {
                if (response.status === 409 || data.errors?.lock_version) form.querySelector('[data-autosave-enabled]').checked = false;
                throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Chưa tự lưu được.');
            }
            form.elements.lock_version.value = data.version;
            dirty = revision !== savedRevision;
            showState(state, dirty ? data.message + ' · còn thay đổi mới chưa lưu.' : data.message);
        } catch (error) {
            showState(state, 'Chưa tự lưu: ' + requestErrorMessage(error, 'Hãy kiểm tra kết nối hoặc phiên đăng nhập rồi thử lại.') + ' Nội dung vẫn giữ trong editor.', true);
        }
    }

    form.querySelector('[data-save-draft]')?.addEventListener('click', () => {
        form.elements.status.value = 'draft';
        form.requestSubmit();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitting) return;
        submitting = true;
        clearTimeout(timer);
        const buttons = [...form.querySelectorAll('button[type="submit"], [data-save-draft]')];
        buttons.forEach(button => { button.disabled = true; });
        try {
            if (autosaveTask) await autosaveTask;
            await prepareContent();
            if (!form.checkValidity()) { form.reportValidity(); throw new Error('Kiểm tra các trường cần nhập.'); }
            dirty = false;
            showState(state, 'Đang lưu bài viết...');
            HTMLFormElement.prototype.submit.call(form);
        } catch (error) {
            submitting = false;
            dirty = true;
            buttons.forEach(button => { button.disabled = false; });
            showState(state, 'Chưa lưu: ' + requestErrorMessage(error, 'Thao tác chưa hoàn tất. Hãy tải lại trang sau khi sao chép nội dung đang viết.') + ' Nội dung vẫn còn trong editor.', true);
        }
    });
}

function init() {
    setupChat();
    setupJournalTheme();
    setupDesktopColumns(document, window, Dropdown);
    setupSidebarFeeds();
    setupSidebarDrawer(Offcanvas);
    setupPhuongComposerBridge(Modal);
    setupLiveUpdates();
    setupThreadMedia();
    setupVoiceRecorder();
    setupPolls();
    setupConnections();
    setupPullToRefresh();
    setupFeedPagination();
    setupInsights();
    setupCommentMentions();
    setupPwa();
    setupLocationGps();
    if (document.querySelector('[data-stream-composer]')) setupThreadStream();
    setupVietnameseValidation();
    setupCommentThreads();
    setupReplyNotifications();
    document.addEventListener('submit', event => {
        const form = event.target.closest('form[data-confirm]');
        if (form && !window.confirm(form.dataset.confirm)) event.preventDefault();
    });
    document.querySelectorAll('[data-auto-submit]').forEach(select => select.addEventListener('change', () => select.form.requestSubmit()));
    document.querySelectorAll('[data-copy]').forEach(button => button.addEventListener('click', () => copyText(button.dataset.copy, button)));
    document.querySelectorAll('[data-share]').forEach(button => button.addEventListener('click', async () => {
        if (navigator.share) {
            try { await navigator.share({ title: document.title, url: button.dataset.share }); return; } catch (error) { if (error.name === 'AbortError') return; }
        }
        await copyText(button.dataset.share, button);
    }));
    setupSeoPreview();
    setupEditor();
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
else init();
