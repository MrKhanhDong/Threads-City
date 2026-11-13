<dialog class="music-picker" data-music-picker data-search-url="{{ route('music.search') }}" aria-labelledby="music-picker-title">
    <div class="music-picker-heading">
        <h2 id="music-picker-title">Thêm nhạc</h2>
        <button type="button" data-music-close aria-label="Đóng bộ chọn nhạc">✕</button>
    </div>
    <form data-music-search role="search">
        <label class="visually-hidden" for="music-search-input">Tìm bài hát hoặc nghệ sĩ</label>
        <input type="search" id="music-search-input" placeholder="Tìm bài hát hoặc nghệ sĩ…" maxlength="100" autocomplete="off">
        <button type="submit">Tìm</button>
    </form>
    <p class="music-picker-status" data-music-status role="status" aria-live="polite"></p>
    <audio data-music-audition controls preload="none" hidden aria-label="Nghe thử bài hát"></audio>
    <div class="music-picker-results" data-music-results role="list" aria-label="Danh sách bài hát"></div>
    <button type="button" class="music-picker-more" data-music-more hidden>Tải thêm</button>
    <p class="music-picker-source">Khám phá nhạc trên <a href="https://audius.co/" target="_blank" rel="noopener noreferrer">Audius</a></p>
</dialog>
