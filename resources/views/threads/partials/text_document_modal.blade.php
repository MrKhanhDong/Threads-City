<!-- Text Document Attachment Modal (Matches Meta Threads "Đính kèm văn bản" exactly) -->
<div class="modal fade" id="textDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 620px;">
        <div class="modal-content text-doc-modal-content">
            
            <!-- Header: Hủy (left) | Đính kèm văn bản (center) | Xong (right) -->
            <div class="text-doc-header">
                <button type="button" class="text-doc-cancel-btn" id="btnTextDocCancel">
                    Hủy
                </button>
                <h5 class="text-doc-title mb-0">Đính kèm văn bản</h5>
                <button type="button" class="text-doc-done-btn" id="btnTextDocDone" disabled>
                    Xong
                </button>
            </div>

            <!-- Body: Rich Text Editable Area -->
            <div class="text-doc-body">
                <div 
                    id="textDocEditor" 
                    class="text-doc-editor" 
                    contenteditable="true" 
                    data-placeholder="Bạn nói gì thêm đi..."
                    spellcheck="false"
                ></div>
            </div>

            <!-- Bottom Toolbar: B | / | U | S | Highlighter/Pen -->
            <div class="text-doc-toolbar">
                <button type="button" class="text-format-btn" data-command="bold" title="In đậm (Ctrl+B)">
                    <span style="font-weight: 800; font-size: 17px;">B</span>
                </button>
                <button type="button" class="text-format-btn" data-command="italic" title="In nghiêng (Ctrl+I)">
                    <span style="font-style: italic; font-size: 18px; font-weight: 500;">/</span>
                </button>
                <button type="button" class="text-format-btn" data-command="underline" title="Gạch chân (Ctrl+U)">
                    <span style="text-decoration: underline; text-underline-offset: 3px; font-weight: 600; font-size: 17px;">U</span>
                </button>
                <button type="button" class="text-format-btn" data-command="strikeThrough" title="Gạch ngang">
                    <span style="text-decoration: line-through; font-weight: 600; font-size: 17px;">S</span>
                </button>
                <button type="button" class="text-format-btn" data-command="code" title="Đánh dấu / Đoạn mã">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 18.5l-4 1 1-4L16.5 3.5z"></path>
                        <line x1="4" y1="22" x2="20" y2="22"></line>
                    </svg>
                </button>
            </div>

        </div>
    </div>
</div>

<style>
/* Text Document Attachment Modal Container */
.text-doc-modal-content {
    background: var(--journal-surface) !important;
    border: 1px solid var(--journal-line) !important;
    border-radius: 20px !important;
    color: var(--journal-text) !important;
    box-shadow: var(--journal-shadow) !important;
    overflow: hidden;
    height: 560px;
    display: flex;
    flex-direction: column;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* Header */
.text-doc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--journal-line);
    flex-shrink: 0;
}
.text-doc-cancel-btn {
    background: transparent;
    border: none;
    color: var(--journal-text);
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    padding: 0;
    transition: opacity 0.15s;
}
.text-doc-cancel-btn:hover {
    opacity: 0.7;
}
.text-doc-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--journal-text);
    letter-spacing: -0.2px;
}
.text-doc-done-btn {
    background: transparent;
    border: none;
    color: var(--journal-faint);
    font-size: 15px;
    font-weight: 700;
    cursor: not-allowed;
    padding: 0;
    transition: color 0.15s, opacity 0.15s;
}
.text-doc-done-btn.is-active {
    color: var(--journal-text) !important;
    cursor: pointer !important;
}
.text-doc-done-btn.is-active:hover {
    opacity: 0.8;
}

/* Editor Area */
.text-doc-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 22px;
    position: relative;
    cursor: text;
}
.text-doc-editor {
    width: 100%;
    min-height: 100%;
    outline: none !important;
    border: none !important;
    color: var(--journal-text);
    font-size: 16px;
    line-height: 1.65;
    word-break: break-word;
}
.text-doc-editor:empty:before {
    content: attr(data-placeholder);
    color: var(--journal-faint);
    pointer-events: none;
}
.text-doc-editor b, 
.text-doc-editor strong {
    font-weight: 700;
    color: var(--journal-text);
}
.text-doc-editor i, 
.text-doc-editor em {
    font-style: italic;
}
.text-doc-editor u {
    text-decoration: underline;
}
.text-doc-editor s, 
.text-doc-editor strike {
    text-decoration: line-through;
}
.text-doc-editor pre, 
.text-doc-editor code {
    background: var(--journal-raised);
    padding: 3px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 14.5px;
    color: var(--journal-success);
}

/* Bottom Formatting Toolbar (B | / | U | S | Code) */
.text-doc-toolbar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 34px;
    padding: 16px 20px 20px;
    flex-shrink: 0;
}
.text-format-btn {
    background: transparent;
    border: none;
    color: var(--journal-text);
    font-size: 17px;
    padding: 6px 10px;
    cursor: pointer;
    border-radius: 8px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s, background 0.15s, transform 0.1s;
    user-select: none;
}
.text-format-btn:hover {
    background: color-mix(in srgb, var(--journal-text) 8%, transparent);
}
.text-format-btn:active {
    transform: scale(0.92);
}
.text-format-btn.active {
    color: var(--journal-link);
    background: rgba(0, 149, 246, 0.12);
}

/* Attachment Card in Composer */
.composer-attached-doc-card {
    background: var(--journal-raised);
    border: 1px solid var(--journal-line);
    border-radius: 14px;
    padding: 12px 16px;
    margin-bottom: 12px;
    position: relative;
    transition: border-color 0.15s;
}
.composer-attached-doc-card:hover {
    border-color: var(--journal-line);
}
.attached-doc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}
.attached-doc-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--journal-link);
    font-size: 12.5px;
    font-weight: 600;
}
.attached-doc-remove-btn {
    background: transparent;
    border: none;
    color: var(--journal-faint);
    cursor: pointer;
    padding: 0;
    line-height: 1;
    transition: color 0.15s;
}
.attached-doc-remove-btn:hover {
    color: var(--journal-text);
}
.attached-doc-preview-text {
    color: var(--journal-text);
    font-size: 14px;
    line-height: 1.5;
    max-height: 72px;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const textDocModalEl = document.getElementById('textDocumentModal');
    const newThreadModalEl = document.getElementById('newThreadModal');
    const textDocEditor = document.getElementById('textDocEditor');
    const btnCancel = document.getElementById('btnTextDocCancel');
    const btnDone = document.getElementById('btnTextDocDone');
    const modalAddListBtn = document.getElementById('modalAddListBtn');
    const formatButtons = document.querySelectorAll('.text-format-btn');

    if (!textDocModalEl) return;

    // Trigger open Text Document modal from 7th icon in composer
    if (modalAddListBtn) {
        modalAddListBtn.setAttribute('title', 'Đính kèm văn bản');
        modalAddListBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.bootstrap) {
                bootstrap.Modal.getInstance(newThreadModalEl)?.hide();
                const textDocModal = bootstrap.Modal.getInstance(textDocModalEl) || new bootstrap.Modal(textDocModalEl);
                textDocModal.show();
                setTimeout(() => {
                    textDocEditor?.focus();
                }, 300);
            }
        });
    }

    // Cancel / Hủy button
    if (btnCancel) {
        btnCancel.addEventListener('click', function() {
            if (window.bootstrap) {
                bootstrap.Modal.getInstance(textDocModalEl)?.hide();
                if (newThreadModalEl) {
                    const composerModal = bootstrap.Modal.getInstance(newThreadModalEl) || new bootstrap.Modal(newThreadModalEl);
                    composerModal.show();
                }
            }
        });
    }

    // Done / Xong button
    if (btnDone) {
        btnDone.addEventListener('click', function() {
            const rawText = (textDocEditor?.innerText || '').trim();
            const rawHtml = (textDocEditor?.innerHTML || '').trim();

            if (!rawText) return;

            // Attach to composer
            attachTextDocToComposer(rawHtml, rawText);

            // Close Text Document modal and reopen composer
            if (window.bootstrap) {
                bootstrap.Modal.getInstance(textDocModalEl)?.hide();
                if (newThreadModalEl) {
                    const composerModal = bootstrap.Modal.getInstance(newThreadModalEl) || new bootstrap.Modal(newThreadModalEl);
                    composerModal.show();
                    setTimeout(() => {
                        document.getElementById('modalComposerText')?.focus();
                    }, 300);
                }
            }
        });
    }

    // Format buttons handler: B, /, U, S, Code
    formatButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const cmd = this.getAttribute('data-command');
            textDocEditor.focus();

            if (cmd === 'code') {
                const selection = window.getSelection();
                if (selection && selection.toString().length > 0) {
                    const selectedText = selection.toString();
                    document.execCommand('insertHTML', false, `<code>${selectedText}</code>`);
                } else {
                    document.execCommand('insertHTML', false, '<code>Đoạn mã</code>&nbsp;');
                }
            } else {
                document.execCommand(cmd, false, null);
            }

            updateFormatToolbarStates();
            updateDoneButtonState();
        });
    });

    // Keyboard shortcuts & input tracking
    if (textDocEditor) {
        textDocEditor.addEventListener('input', function() {
            updateDoneButtonState();
            updateFormatToolbarStates();
        });

        textDocEditor.addEventListener('keyup', updateFormatToolbarStates);
        textDocEditor.addEventListener('mouseup', updateFormatToolbarStates);
    }

    function updateDoneButtonState() {
        const text = (textDocEditor?.innerText || '').trim();
        if (btnDone) {
            if (text.length > 0) {
                btnDone.disabled = false;
                btnDone.classList.add('is-active');
            } else {
                btnDone.disabled = true;
                btnDone.classList.remove('is-active');
            }
        }
    }

    function updateFormatToolbarStates() {
        formatButtons.forEach(btn => {
            const cmd = btn.getAttribute('data-command');
            if (cmd !== 'code') {
                if (document.queryCommandState(cmd)) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            }
        });
    }

    function attachTextDocToComposer(html, plainText) {
        const composerTextarea = document.getElementById('modalComposerText');
        const flowRightCol = document.querySelector('.composer-right-col');
        
        let existingCard = document.getElementById('composerAttachedDocCard');
        if (!existingCard) {
            existingCard = document.createElement('div');
            existingCard.id = 'composerAttachedDocCard';
            existingCard.className = 'composer-attached-doc-card';
            
            // Insert above toolbar strip
            const toolbar = document.getElementById('composerToolbarStrip');
            if (toolbar && toolbar.parentNode) {
                toolbar.parentNode.insertBefore(existingCard, toolbar);
            }
        }

        existingCard.style.display = 'block';
        existingCard.innerHTML = `
            <div class="attached-doc-header">
                <div class="attached-doc-badge">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <span>Văn bản đính kèm</span>
                </div>
                <button type="button" class="attached-doc-remove-btn" id="btnRemoveAttachedDoc" title="Gỡ văn bản">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="attached-doc-preview-text">${html}</div>
            <input type="hidden" name="attached_document_html" id="modalAttachedDocHtml" value="${encodeURIComponent(html)}">
        `;

        document.getElementById('btnRemoveAttachedDoc')?.addEventListener('click', function(e) {
            e.preventDefault();
            existingCard.style.display = 'none';
            existingCard.innerHTML = '';
            if (textDocEditor) textDocEditor.innerHTML = '';
            updateDoneButtonState();
        });

        // If composer textarea is empty, give it focus
        if (composerTextarea && !composerTextarea.value.trim()) {
            composerTextarea.focus();
        }

        // Enable submit button
        const submitBtn = document.getElementById('modalSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.add('is-active');
        }
    }
});
</script>
