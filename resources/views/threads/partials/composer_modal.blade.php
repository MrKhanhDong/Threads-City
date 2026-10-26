<!-- New Thread / Quote Modal -->
<div class="modal fade" id="newThreadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
        <div class="modal-content" style="background: #181818; border: 1px solid #2e2e2e; border-radius: 20px; color: #fff; box-shadow: 0 10px 40px rgba(0,0,0,0.85); overflow: hidden;">
            <div class="modal-header border-bottom border-dark px-4 py-3">
                <h5 class="modal-title fs-6 fw-bold text-white mb-0" id="newThreadModalTitle">Bài viết mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3" id="modalDropZone">
                <form id="modalThreadForm" action="{{ route('threads.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="parent_id" id="modalParentId" value="">
                    <input type="hidden" name="repost_of_id" id="modalRepostOfId" value="">
                    <input type="hidden" name="type" id="modalPostType" value="post">
                    <div id="modalMediaIdsContainer"></div>

                    <!-- Author Info -->
                    <div class="d-flex gap-3 align-items-start mb-2">
                        @if(auth()->user()->avatar_path)
                            <img src="{{ route('avatar.show', auth()->user()->id) }}" alt="{{ auth()->user()->name }}" class="user-avatar-round">
                        @else
                            <div class="user-avatar-letter">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                        @endif
                        <div class="flex-grow-1 position-relative">
                            <div class="fw-semibold text-white fs-6 mb-1">{{ auth()->user()->handle() }}</div>
                            <textarea 
                                name="content" 
                                id="modalComposerText" 
                                class="w-100 bg-transparent border-0 text-white outline-none p-0" 
                                style="font-size: 15px; resize: none; min-height: 90px; outline: none !important;"
                                placeholder="Có gì mới hôm nay?..." 
                                maxlength="2000"
                            ></textarea>
                            <div id="modalMentionDropdown" class="mention-autocomplete-dropdown" style="display: none;"></div>
                        </div>
                    </div>

                    <!-- Quoted thread preview container (if quoting) -->
                    <div id="modalQuotedThreadContainer" class="p-3 mb-3 rounded-3" style="display: none; background: #202020; border: 1px solid #333;"></div>

                    <!-- Media previews grid -->
                    <div id="modalMediaPreview" class="modal-media-grid mb-3" style="display: none;"></div>

                    <!-- Drag & Drop Overlay indicator -->
                    <div id="modalDropZonePrompt" class="modal-drop-zone-prompt text-center py-3 mb-3 rounded-3" style="display: none;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0095f6" stroke-width="2" class="mb-1">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <div class="text-white small fw-semibold">Thả ảnh vào đây để tải lên</div>
                        <div class="text-secondary" style="font-size: 11px;">Hỗ trợ tối đa 4 ảnh (JPG, PNG, WebP, GIF)</div>
                    </div>

                    <!-- Toolbar -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-dark">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm text-white border-0 p-1 btn-modal-attach" id="modalAddImageBtn" title="Đính kèm ảnh (Tối đa 4 ảnh)">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                            </button>
                            <input type="file" id="modalComposerFileInput" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif" multiple hidden>
                            <span id="modalCharCount" class="text-secondary small">0/2000</span>
                            <span id="modalImageBadge" class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25" style="display: none;">0/4 ảnh</span>
                        </div>
                        <button type="submit" class="btn-threads-primary px-4" id="modalSubmitBtn" disabled>
                            <span id="modalSubmitText">Đăng</span>
                            <span id="modalSubmitSpinner" class="spinner-border spinner-border-sm ms-2" style="display: none;" role="status"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.modal-media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 10px;
    max-height: 240px;
    overflow-y: auto;
    padding: 2px;
}
.modal-media-thumb {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 1;
    border: 1px solid #333333;
    background: #000000;
}
.modal-media-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.modal-media-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.75);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    transition: background 0.15s, transform 0.15s;
}
.modal-media-remove:hover {
    background: #ff3040;
    transform: scale(1.1);
}
.modal-media-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #111111;
    color: #888888;
    font-size: 11px;
    gap: 6px;
    height: 100%;
}
.btn-modal-attach {
    color: #777777 !important;
    transition: color 0.15s, transform 0.15s;
}
.btn-modal-attach:hover {
    color: #ffffff !important;
    transform: scale(1.08);
}
.modal-drop-zone-prompt {
    border: 2px dashed #0095f6;
    background: rgba(0, 149, 246, 0.08);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('newThreadModal');
    if (!modalEl) return;

    const modalForm = document.getElementById('modalThreadForm');
    const modalText = document.getElementById('modalComposerText');
    const modalFileInput = document.getElementById('modalComposerFileInput');
    const modalAddImageBtn = document.getElementById('modalAddImageBtn');
    const modalPreview = document.getElementById('modalMediaPreview');
    const modalMediaIdsContainer = document.getElementById('modalMediaIdsContainer');
    const modalSubmitBtn = document.getElementById('modalSubmitBtn');
    const modalSubmitText = document.getElementById('modalSubmitText');
    const modalSubmitSpinner = document.getElementById('modalSubmitSpinner');
    const modalCharCount = document.getElementById('modalCharCount');
    const modalImageBadge = document.getElementById('modalImageBadge');
    const modalDropZonePrompt = document.getElementById('modalDropZonePrompt');
    const dropZone = document.getElementById('modalDropZone');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const MAX_IMAGES = 4;
    let uploadedMedia = []; // Array of { id, url }
    let isUploadingCount = 0;

    function toast(msg) {
        const t = document.getElementById('threadsGlobalToast');
        if (t) {
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 2500);
        } else {
            console.log(msg);
        }
    }

    function updateSubmitState() {
        const textLen = (modalText?.value || '').trim().length;
        const mediaCount = uploadedMedia.length;
        
        if (modalCharCount) {
            modalCharCount.textContent = `${textLen}/2000`;
        }

        if (modalImageBadge) {
            if (mediaCount > 0) {
                modalImageBadge.textContent = `${mediaCount}/${MAX_IMAGES} ảnh`;
                modalImageBadge.style.display = 'inline-block';
            } else {
                modalImageBadge.style.display = 'none';
            }
        }

        const canSubmit = (textLen > 0 || mediaCount > 0) && isUploadingCount === 0;
        if (modalSubmitBtn) {
            modalSubmitBtn.disabled = !canSubmit;
        }

        if (modalAddImageBtn) {
            modalAddImageBtn.style.opacity = (mediaCount >= MAX_IMAGES || isUploadingCount > 0) ? '0.4' : '1';
            modalAddImageBtn.disabled = (mediaCount >= MAX_IMAGES || isUploadingCount > 0);
        }
    }

    // Auto-grow textarea
    if (modalText) {
        modalText.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(90, this.scrollHeight) + 'px';
            updateSubmitState();
        });

        // Clipboard Paste Image support (Ctrl+V)
        modalText.addEventListener('paste', function(e) {
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            const files = [];
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const blob = items[i].getAsFile();
                    if (blob) files.push(blob);
                }
            }
            if (files.length > 0) {
                handleFilesUpload(files);
            }
        });

        // Initialize mention autocomplete
        if (window.setupMentionAutocomplete) {
            window.setupMentionAutocomplete(modalText, document.getElementById('modalMentionDropdown'));
        }
    }

    // Trigger file chooser
    if (modalAddImageBtn && modalFileInput) {
        modalAddImageBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (uploadedMedia.length >= MAX_IMAGES) {
                toast(`Tối đa ${MAX_IMAGES} ảnh cho mỗi bài viết`);
                return;
            }
            modalFileInput.click();
        });

        modalFileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                handleFilesUpload(Array.from(this.files));
                this.value = ''; // Reset for re-selection
            }
        });
    }

    // Drag and drop handlers
    if (dropZone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (modalDropZonePrompt) modalDropZonePrompt.style.display = 'block';
            }, false);
        });

        ['dragleave', 'dragend'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (modalDropZonePrompt) modalDropZonePrompt.style.display = 'none';
            }, false);
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (modalDropZonePrompt) modalDropZonePrompt.style.display = 'none';
            const dt = e.dataTransfer;
            const files = dt ? Array.from(dt.files).filter(f => f.type.startsWith('image/')) : [];
            if (files.length > 0) {
                handleFilesUpload(files);
            }
        }, false);
    }

    function renderPreviews() {
        if (!modalPreview || !modalMediaIdsContainer) return;
        modalMediaIdsContainer.innerHTML = '';
        modalPreview.innerHTML = '';

        if (uploadedMedia.length === 0) {
            modalPreview.style.display = 'none';
            updateSubmitState();
            return;
        }

        modalPreview.style.display = 'grid';
        uploadedMedia.forEach((media, idx) => {
            // Hidden input for form submission
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'media_ids[]';
            input.value = media.id;
            modalMediaIdsContainer.appendChild(input);

            // Thumbnail card
            const thumb = document.createElement('div');
            thumb.className = 'modal-media-thumb';
            thumb.innerHTML = `
                <img src="${media.url}" alt="Attachment ${idx + 1}" loading="lazy">
                <button type="button" class="modal-media-remove" data-idx="${idx}" title="Xóa ảnh">✕</button>
            `;
            modalPreview.appendChild(thumb);
        });

        updateSubmitState();
    }

    // Remove photo handler
    modalPreview?.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.modal-media-remove');
        if (!removeBtn) return;
        e.preventDefault();
        const idx = parseInt(removeBtn.getAttribute('data-idx'));
        if (!isNaN(idx) && idx >= 0 && idx < uploadedMedia.length) {
            uploadedMedia.splice(idx, 1);
            renderPreviews();
        }
    });

    function handleFilesUpload(files) {
        const availableSlots = MAX_IMAGES - uploadedMedia.length - isUploadingCount;
        if (availableSlots <= 0) {
            toast(`Bạn chỉ có thể đính kèm tối đa ${MAX_IMAGES} ảnh.`);
            return;
        }

        const filesToUpload = files.slice(0, availableSlots);
        if (files.length > availableSlots) {
            toast(`Đã chọn quá ${MAX_IMAGES} ảnh. Chỉ tải lên ${availableSlots} ảnh đầu.`);
        }

        filesToUpload.forEach(file => {
            if (!file.type.startsWith('image/')) {
                toast('Chỉ chấp nhận file định dạng hình ảnh.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                toast(`File "${file.name}" vượt quá 5MB.`);
                return;
            }

            // Create placeholder loading item
            isUploadingCount++;
            updateSubmitState();

            modalPreview.style.display = 'grid';
            const placeholder = document.createElement('div');
            placeholder.className = 'modal-media-thumb modal-media-loading';
            placeholder.innerHTML = `
                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                <span>Đang tải...</span>
            `;
            modalPreview.appendChild(placeholder);

            const formData = new FormData();
            formData.append('image', file);

            fetch('{{ route("threads.upload-media") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(errData => { throw new Error(errData.message || 'Lỗi tải ảnh'); });
                }
                return res.json();
            })
            .then(data => {
                if (data && data.id && data.url) {
                    uploadedMedia.push({ id: data.id, url: data.url });
                    toast('Đã tải ảnh lên thành công');
                } else {
                    toast('Không nhận được dữ liệu ảnh tải lên');
                }
            })
            .catch(err => {
                console.error(err);
                toast(err.message || 'Tải ảnh thất bại');
            })
            .finally(() => {
                placeholder.remove();
                isUploadingCount = Math.max(0, isUploadingCount - 1);
                renderPreviews();
            });
        });
    }

    // Modal Form Submission (AJAX)
    modalForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        const content = (modalText?.value || '').trim();
        if (!content && uploadedMedia.length === 0) {
            toast('Vui lòng nhập nội dung hoặc đính kèm ảnh');
            return;
        }

        if (modalSubmitBtn) modalSubmitBtn.disabled = true;
        if (modalSubmitSpinner) modalSubmitSpinner.style.display = 'inline-block';
        if (modalSubmitText) modalSubmitText.textContent = 'Đang đăng...';

        const parentId = document.getElementById('modalParentId')?.value || null;
        const repostOfId = document.getElementById('modalRepostOfId')?.value || null;
        const postType = document.getElementById('modalPostType')?.value || 'post';

        fetch(modalForm.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                content: content,
                parent_id: parentId,
                repost_of_id: repostOfId,
                type: postType,
                media_ids: uploadedMedia.map(m => m.id)
            })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.message || 'Không thể đăng bài viết'); });
            }
            return res.json();
        })
        .then(data => {
            if (data && (data.success || data.thread)) {
                toast('Đã đăng bài viết thành công!');
                // Close modal
                if (window.bootstrap) {
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                }
                // Reset form
                resetModal();
                // Reload feed smoothly
                setTimeout(() => {
                    location.reload();
                }, 600);
            } else {
                toast(data.message || 'Đăng bài thất bại');
                if (modalSubmitBtn) modalSubmitBtn.disabled = false;
                if (modalSubmitSpinner) modalSubmitSpinner.style.display = 'none';
                if (modalSubmitText) modalSubmitText.textContent = 'Đăng';
            }
        })
        .catch(err => {
            console.error(err);
            toast(err.message || 'Có lỗi xảy ra khi đăng bài');
            if (modalSubmitBtn) modalSubmitBtn.disabled = false;
            if (modalSubmitSpinner) modalSubmitSpinner.style.display = 'none';
            if (modalSubmitText) modalSubmitText.textContent = 'Đăng';
        });
    });

    function resetModal() {
        if (modalForm) modalForm.reset();
        uploadedMedia = [];
        isUploadingCount = 0;
        if (modalMediaIdsContainer) modalMediaIdsContainer.innerHTML = '';
        if (modalPreview) {
            modalPreview.innerHTML = '';
            modalPreview.style.display = 'none';
        }
        if (modalText) {
            modalText.value = '';
            modalText.style.height = '90px';
        }
        document.getElementById('modalParentId').value = '';
        document.getElementById('modalRepostOfId').value = '';
        document.getElementById('modalPostType').value = 'post';
        document.getElementById('newThreadModalTitle').textContent = 'Bài viết mới';
        const quoteContainer = document.getElementById('modalQuotedThreadContainer');
        if (quoteContainer) {
            quoteContainer.style.display = 'none';
            quoteContainer.innerHTML = '';
        }
        if (modalSubmitBtn) modalSubmitBtn.disabled = true;
        if (modalSubmitSpinner) modalSubmitSpinner.style.display = 'none';
        if (modalSubmitText) modalSubmitText.textContent = 'Đăng';
        updateSubmitState();
    }

    modalEl.addEventListener('hidden.bs.modal', function() {
        resetModal();
    });

    // Expose trigger for quick upload
    window.openThreadComposerWithImagePicker = function() {
        if (window.bootstrap) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
            setTimeout(() => {
                modalFileInput?.click();
            }, 350);
        }
    };
});
</script>
