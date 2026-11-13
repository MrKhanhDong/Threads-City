<dialog class="gif-picker" data-gif-picker aria-labelledby="gif-picker-title">
    <div class="gif-picker-heading">
        <h2 id="gif-picker-title">Chọn GIF</h2>
        <button type="button" data-gif-close aria-label="Đóng bộ chọn GIF">✕</button>
    </div>
    <form data-gif-search role="search">
        <label class="visually-hidden" for="gif-search-input">Tìm GIF trên GIPHY</label>
        <input type="search" id="gif-search-input" placeholder="Tìm GIF: vui, mèo, chúc mừng…" maxlength="50" autocomplete="off">
        <button type="submit">Tìm</button>
    </form>
    <p class="gif-picker-status" data-gif-status role="status" aria-live="polite"></p>
    <div class="gif-picker-results" data-gif-results aria-label="Kết quả GIF"></div>
    <button type="button" class="gif-picker-more" data-gif-more hidden>Tải thêm</button>
    <div class="gif-picker-footer">
        <button type="button" data-gif-upload>Chọn GIF từ máy</button>
        <a href="https://giphy.com/" target="_blank" rel="noopener noreferrer" class="giphy-attribution"><img src="{{ asset('giphy-attribution.png') }}" alt="Powered by GIPHY" width="130"></a>
    </div>
</dialog>
