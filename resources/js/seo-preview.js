const text = value => typeof value === 'string' ? value.trim() : '';

export function previewSlug(value) {
    return text(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[đĐ]/g, 'd')
        .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

function httpUrl(value, base) {
    try {
        const url = new URL(value, base);
        return ['http:', 'https:'].includes(url.protocol) ? url : null;
    } catch { return null; }
}

export function buildSeoPreview(values, baseUrl) {
    const title = text(values.title) || 'Tiêu đề bài viết';
    const slug = text(values.slug) || previewSlug(text(values.title)) || 'duong-dan-bai-viet';
    const fallbackUrl = baseUrl.replace(/\/$/, '') + '/' + encodeURIComponent(slug);
    const canonical = text(values.canonical_url);
    const url = canonical && httpUrl(canonical) ? canonical : fallbackUrl;
    const description = text(values.meta_description) || text(values.excerpt) || 'Thêm mô tả để độc giả biết câu chuyện nói về điều gì.';
    const selectedImage = text(values.og_image) || text(values.cover_image);
    const parsedImage = selectedImage ? httpUrl(selectedImage, baseUrl) : null;
    const origin = httpUrl(baseUrl)?.origin;
    return {
        searchTitle: text(values.seo_title) || title,
        description,
        socialTitle: text(values.og_title) || title,
        socialDescription: text(values.og_description) || description,
        url,
        domain: httpUrl(url)?.hostname || '',
        image: parsedImage && parsedImage.origin === origin ? parsedImage.href : '',
    };
}

export function renderSeoPreview(panel, values, baseUrl) {
    const preview = buildSeoPreview(values, baseUrl);
    const fields = {
        'search-title': preview.searchTitle,
        'description': preview.description,
        'social-title': preview.socialTitle,
        'social-description': preview.socialDescription,
        url: preview.url,
        domain: preview.domain,
        lengths: Array.from(preview.searchTitle).length + ' ký tự tiêu đề · ' + Array.from(preview.description).length + ' ký tự mô tả',
    };
    Object.entries(fields).forEach(([name, value]) => {
        const element = panel.querySelector('[data-seo-' + name + ']');
        if (element) element.textContent = value;
    });
    const image = panel.querySelector('[data-seo-image]');
    if (image) {
        image.hidden = !preview.image;
        if (preview.image) image.setAttribute('src', preview.image);
        else image.removeAttribute('src');
    }
}

export function setupSeoPreview() {
    const form = document.querySelector('[data-editor-form]');
    const panel = form?.querySelector('[data-seo-preview]');
    if (!panel) return;
    const update = () => {
        const values = {};
        ['title', 'slug', 'excerpt', 'seo_title', 'meta_description', 'canonical_url', 'og_title', 'og_description']
            .forEach(name => { values[name] = form.elements.namedItem(name)?.value || ''; });
        values.cover_image = form.elements.namedItem('cover_media_id')?.dataset.previewUrl || '';
        values.og_image = form.elements.namedItem('og_media_id')?.selectedOptions[0]?.dataset.previewUrl || '';
        renderSeoPreview(panel, values, panel.dataset.seoBaseUrl);
    };
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
}
