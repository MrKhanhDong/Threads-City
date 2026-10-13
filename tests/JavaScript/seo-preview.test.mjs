import test from 'node:test';
import assert from 'node:assert/strict';
import { buildSeoPreview, previewSlug, renderSeoPreview } from '../../resources/js/seo-preview.js';

const base = 'http://Doan.test/bai-viet';

test('Vietnamese titles generate a readable draft URL', () => {
    assert.equal(previewSlug('Ghi lại những điều nhỏ. ĐẸP!'), 'ghi-lai-nhung-dieu-nho-dep');
    const preview = buildSeoPreview({ title: 'Một góc viết', excerpt: 'Giới thiệu' }, base);
    assert.equal(preview.searchTitle, 'Một góc viết');
    assert.equal(preview.socialTitle, 'Một góc viết');
    assert.equal(preview.description, 'Giới thiệu');
    assert.equal(preview.url, base + '/mot-goc-viet');
});

test('search and social overrides follow the public metadata fallback rules', () => {
    const preview = buildSeoPreview({
        title: 'Gốc', slug: 'slug-rieng', seo_title: 'SEO', meta_description: 'Meta',
        og_title: 'OG', og_description: 'OG mô tả', canonical_url: 'https://example.com/story',
        cover_image: 'http://Doan.test/media/1', og_image: 'http://Doan.test/media/2'
    }, base);
    assert.equal(preview.searchTitle, 'SEO');
    assert.equal(preview.socialTitle, 'OG');
    assert.equal(preview.socialDescription, 'OG mô tả');
    assert.equal(preview.url, 'https://example.com/story');
    assert.equal(preview.domain, 'example.com');
    assert.equal(preview.image, 'http://doan.test/media/2');
});

test('clearing overrides returns to normal fallback values', () => {
    const preview = buildSeoPreview({ title: 'Gốc', seo_title: ' ', meta_description: '', excerpt: 'Giới thiệu', og_image: '', cover_image: '/media/1' }, base);
    assert.equal(preview.searchTitle, 'Gốc');
    assert.equal(preview.socialDescription, 'Giới thiệu');
    assert.equal(preview.image, 'http://doan.test/media/1');
});

test('unsafe canonical schemes and external images are never used', () => {
    for (const url of ['javascript:alert(1)', 'data:text/html,bad', 'file:///secret', 'not a URL']) {
        assert.equal(buildSeoPreview({ title: 'Gốc', canonical_url: url }, base).url, base + '/goc');
    }
    for (const image of ['javascript:alert(1)', 'data:image/png,bad', 'https://tracker.example/image.png']) {
        assert.equal(buildSeoPreview({ og_image: image }, base).image, '');
    }
});

test('empty and non-string values do not crash the draft preview', () => {
    const preview = buildSeoPreview({ title: [], seo_title: null, canonical_url: {} }, base);
    assert.equal(preview.searchTitle, 'Tiêu đề bài viết');
    assert.equal(preview.url, base + '/duong-dan-bai-viet');
    assert.equal(preview.image, '');
});

test('rendering writes text, not HTML, and removes a cleared image', () => {
    const elements = new Map();
    const panel = { querySelector(selector) {
        if (!elements.has(selector)) elements.set(selector, {
            textContent: '', hidden: false, attributes: {},
            setAttribute(name, value) { this.attributes[name] = value; },
            removeAttribute(name) { delete this.attributes[name]; },
            set innerHTML(value) { throw new Error('Unsafe HTML write: ' + value); }
        });
        return elements.get(selector);
    }};
    renderSeoPreview(panel, { title: '<script>alert(1)</script>', cover_image: '/media/1' }, base);
    assert.equal(elements.get('[data-seo-search-title]').textContent, '<script>alert(1)</script>');
    const image = elements.get('[data-seo-image]');
    assert.equal(image.attributes.src, 'http://doan.test/media/1');
    assert.equal(image.hidden, false);
    renderSeoPreview(panel, { title: 'Mới' }, base);
    assert.equal(image.hidden, true);
    assert.equal(image.attributes.src, undefined);
    assert.equal(elements.get('[data-seo-search-title]').textContent, 'Mới');
    renderSeoPreview(panel, { title: 'Mộc 🌿', meta_description: 'Đẹp 🌿' }, base);
    assert.equal(elements.get('[data-seo-lengths]').textContent, '5 ký tự tiêu đề · 5 ký tự mô tả');
});
