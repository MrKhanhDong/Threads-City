(() => {
    "use strict";
    let toastTimer;
    const notify = (message) => {
        const toast = document.getElementById("toast");
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add("is-visible");
        clearTimeout(toastTimer);
        toastTimer = setTimeout(
            () => toast.classList.remove("is-visible"),
            3500,
        );
    };

    document.querySelectorAll("form[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    document.querySelectorAll("[data-copy-link]").forEach((button) => {
        button.addEventListener("click", async () => {
            try {
                await navigator.clipboard.writeText(window.location.href);
                notify("Đã sao chép liên kết bài viết.");
            } catch {
                notify(
                    "Không thể sao chép. Bạn có thể lấy liên kết trên thanh địa chỉ.",
                );
            }
        });
    });

    const editor = document.querySelector("[data-editor]");
    if (!editor) return;
    const body = editor.elements.namedItem("content");
    const count = document.querySelector("[data-word-count]");
    const updateCount = () => {
        if (count)
            count.textContent =
                (body.value.trim().match(/\S+/gu) || []).length + " từ";
    };
    body.addEventListener("input", updateCount);
    updateCount();

    const dialog = document.querySelector("[data-preview-dialog]");
    const preview = document.querySelector("[data-preview]");
    if (dialog && preview) {
        preview.addEventListener("click", () => {
            dialog.querySelector("[data-preview-title]").textContent =
                editor.elements.namedItem("title").value ||
                "Bài viết chưa có tiêu đề";
            dialog.querySelector("[data-preview-excerpt]").textContent =
                editor.elements.namedItem("excerpt").value;
            const category = editor.elements.namedItem("category_id");
            dialog.querySelector("[data-preview-category]").textContent =
                category.selectedOptions[0]?.textContent || "";
            const container = dialog.querySelector("[data-preview-body]");
            container.replaceChildren();
            body.value
                .split(/\r?\n\s*\r?\n|(?=^##\s)/mu)
                .filter((block) => block.trim())
                .forEach((block) => {
                    const heading = block
                        .trim()
                        .match(/^##\s+([^\n]+)(?:\n([\s\S]*))?$/u);
                    if (heading) {
                        const title = document.createElement("h2");
                        title.textContent = heading[1];
                        container.append(title);
                        if (heading[2]) {
                            const paragraph = document.createElement("p");
                            paragraph.textContent = heading[2].trim();
                            container.append(paragraph);
                        }
                    } else {
                        const paragraph = document.createElement("p");
                        paragraph.textContent = block.trim();
                        container.append(paragraph);
                    }
                });
            if (typeof dialog.showModal === "function") dialog.showModal();
            else
                notify(
                    "Trình duyệt này chưa hỗ trợ hộp xem trước. Bạn vẫn có thể lưu bài bình thường.",
                );
        });
        document
            .querySelector("[data-close-preview]")
            ?.addEventListener("click", () => dialog.close());
        dialog.addEventListener("click", (event) => {
            if (event.target === dialog) dialog.close();
        });
    }

    const upload = editor.querySelector("[data-cover-input]");
    const image = editor.querySelector("[data-cover-preview]");
    let imageUrl;
    if (upload && image) {
        upload.addEventListener("change", () => {
            if (imageUrl) URL.revokeObjectURL(imageUrl);
            image.removeAttribute("src");
            image.hidden = true;
            const file = upload.files?.[0];
            if (!file) return;
            if (
                !["image/jpeg", "image/png", "image/webp"].includes(
                    file.type,
                ) ||
                file.size > 5 * 1024 * 1024
            ) {
                upload.value = "";
                notify("Chọn ảnh JPG, PNG hoặc WebP không quá 5 MB.");
                return;
            }
            imageUrl = URL.createObjectURL(file);
            image.src = imageUrl;
            image.hidden = false;
        });
    }
})();
