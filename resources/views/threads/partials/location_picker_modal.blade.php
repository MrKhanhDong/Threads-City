<!-- Location Picker Modal (Matches Meta Threads "Chọn địa điểm" exactly) -->
<div class="modal fade" id="locationPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 620px;">
        <div class="modal-content location-picker-modal-content">
            
            <!-- Header: Back Arrow (left) | Chọn địa điểm (center) -->
            <div class="location-picker-header">
                <button type="button" class="location-picker-back-btn" id="btnLocationPickerBack" title="Quay lại">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </button>
                <h5 class="location-picker-title mb-0">Chọn địa điểm</h5>
                <div style="width: 28px;"></div> <!-- Spacer for symmetry -->
            </div>

            <!-- Search Bar: Search Icon | Input | GPS navigation arrow -->
            <div class="location-search-wrap">
                <div class="location-search-bar">
                    <svg class="location-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#777777" stroke-width="2.2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input 
                        type="text" 
                        id="locationSearchInput" 
                        class="location-search-input" 
                        placeholder="Tìm kiếm địa điểm" 
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <button type="button" class="location-gps-btn" id="btnLocationGPS" title="Lấy vị trí hiện tại">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body: List of Locations -->
            <div class="location-picker-body">
                <div id="locationPickerList" class="location-picker-list">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Location Picker Modal Container */
.location-picker-modal-content {
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
.location-picker-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px 14px;
    border-bottom: 1px solid var(--journal-line);
    flex-shrink: 0;
}
.location-picker-back-btn {
    background: transparent;
    border: none;
    color: var(--journal-text);
    cursor: pointer;
    padding: 4px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: opacity 0.15s, background 0.15s;
}
.location-picker-back-btn:hover {
    background: color-mix(in srgb, var(--journal-text) 10%, transparent);
    opacity: 0.9;
}
.location-picker-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--journal-text);
    letter-spacing: -0.2px;
}

/* Search Bar */
.location-search-wrap {
    padding: 12px 18px 8px;
    flex-shrink: 0;
}
.location-search-bar {
    position: relative;
    display: flex;
    align-items: center;
    background: var(--journal-raised);
    border: 1px solid var(--journal-line);
    border-radius: 12px;
    padding: 0 14px;
    transition: border-color 0.15s, background 0.15s;
}
.location-search-bar:focus-within {
    border-color: var(--journal-line);
    background: var(--journal-raised);
}
.location-search-icon {
    flex-shrink: 0;
    margin-right: 10px;
}
.location-search-input {
    flex: 1;
    background: transparent;
    border: none !important;
    outline: none !important;
    color: var(--journal-text);
    font-size: 15px;
    padding: 10px 0;
}
.location-search-input::placeholder {
    color: var(--journal-faint);
}
.location-gps-btn {
    background: transparent;
    border: none;
    color: var(--journal-muted);
    cursor: pointer;
    padding: 6px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: color 0.15s, background 0.15s;
}
.location-gps-btn:hover {
    color: var(--journal-link);
    background: rgba(0, 149, 246, 0.1);
}

/* Body & List */
.location-picker-body {
    flex: 1;
    overflow-y: auto;
    padding: 4px 10px 16px;
}
.location-picker-list {
    display: flex;
    flex-direction: column;
}
.location-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.12s;
    user-select: none;
}
.location-item:hover {
    background: var(--journal-raised);
}
.location-item:active {
    background: var(--journal-raised);
}
.location-item-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--journal-raised);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--journal-muted);
    transition: background 0.12s, color 0.12s;
}
.location-item:hover .location-item-icon-box {
    background: var(--journal-hover);
    color: var(--journal-text);
}
.location-item-custom .location-item-icon-box {
    background: rgba(0, 149, 246, 0.15);
    color: var(--journal-link);
}
.location-item-content {
    flex: 1;
    min-width: 0;
}
.location-item-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--journal-text);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.location-item-address {
    font-size: 13px;
    color: var(--journal-faint);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.location-empty-state {
    padding: 40px 20px;
    text-align: center;
    color: var(--journal-faint);
    font-size: 14.5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const locationModalEl = document.getElementById('locationPickerModal');
    const newThreadModalEl = document.getElementById('newThreadModal');
    const searchInput = document.getElementById('locationSearchInput');
    const listContainer = document.getElementById('locationPickerList');
    const btnBack = document.getElementById('btnLocationPickerBack');
    const btnGps = document.getElementById('btnLocationGPS');
    const modalAddLocationBtn = document.getElementById('modalAddLocationBtn');

    if (!locationModalEl) return;

    // Default popular and prominent locations in Vietnam
    const DEFAULT_LOCATIONS = [
        { name: "TP. Hồ Chí Minh", address: "Thành phố Hồ Chí Minh, Việt Nam" },
        { name: "Hà Nội", address: "Thành phố Hà Nội, Việt Nam" },
        { name: "Đà Nẵng", address: "Thành phố Đà Nẵng, Việt Nam" },
        { name: "Trường Đại học Công Thương TP.HCM (HUIT)", address: "140 Lê Trọng Tấn, Tây Thạnh, Tân Phú, TP.HCM" },
        { name: "Quận 1", address: "TP. Hồ Chí Minh, Việt Nam" },
        { name: "Landmark 81", address: "720A Điện Biên Phủ, Vinhomes Central Park, Bình Thạnh, TP.HCM" },
        { name: "Phố đi bộ Nguyễn Huệ", address: "Bến Nghé, Quận 1, TP. Hồ Chí Minh" },
        { name: "Hồ Con Rùa", address: "Phường 6, Quận 3, TP. Hồ Chí Minh" },
        { name: "Hồ Hoàn Kiếm", address: "Hàng Trống, Hoàn Kiếm, Hà Nội" },
        { name: "Thành phố Thủ Đức", address: "TP. Hồ Chí Minh, Việt Nam" },
        { name: "Đà Lạt", address: "Lâm Đồng, Việt Nam" },
        { name: "Vũng Tàu", address: "Bà Rịa - Vũng Tàu, Việt Nam" },
        { name: "Nha Trang", address: "Khánh Hòa, Việt Nam" },
        { name: "Cần Thơ", address: "Thành phố Cần Thơ, Việt Nam" },
        { name: "Hạ Long", address: "Quảng Ninh, Việt Nam" },
        { name: "Huế", address: "Thừa Thiên Huế, Việt Nam" },
        { name: "Phú Quốc", address: "Kiên Giang, Việt Nam" }
    ];

    function renderLocationList(filterQuery = '') {
        if (!listContainer) return;
        const q = filterQuery.trim().toLowerCase();
        let itemsHtml = '';

        // If user typed a search term, allow choosing custom location
        if (q.length > 0) {
            itemsHtml += `
                <div class="location-item location-item-custom" data-name="${encodeURIComponent(filterQuery.trim())}">
                    <div class="location-item-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="location-item-content">
                        <div class="location-item-name">Sử dụng "${filterQuery.trim()}"</div>
                        <div class="location-item-address">Đính kèm địa điểm tùy chỉnh này</div>
                    </div>
                </div>
            `;
        }

        const filtered = DEFAULT_LOCATIONS.filter(item => {
            if (!q) return true;
            return item.name.toLowerCase().includes(q) || item.address.toLowerCase().includes(q);
        });

        if (filtered.length === 0 && !q) {
            itemsHtml += '<div class="location-empty-state">Không tìm thấy địa điểm</div>';
        } else {
            filtered.forEach(loc => {
                itemsHtml += `
                    <div class="location-item" data-name="${encodeURIComponent(loc.name)}">
                        <div class="location-item-icon-box">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <div class="location-item-content">
                            <div class="location-item-name">${loc.name}</div>
                            <div class="location-item-address">${loc.address}</div>
                        </div>
                    </div>
                `;
            });
        }

        listContainer.innerHTML = itemsHtml;

        // Bind clicks on location items
        listContainer.querySelectorAll('.location-item').forEach(el => {
            el.addEventListener('click', function() {
                const locName = decodeURIComponent(this.getAttribute('data-name') || '');
                if (locName) {
                    selectLocation(locName);
                }
            });
        });
    }

    // Initial render
    renderLocationList();

    // Trigger open Location modal from icon 8 in composer
    if (modalAddLocationBtn) {
        modalAddLocationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.bootstrap) {
                bootstrap.Modal.getInstance(newThreadModalEl)?.hide();
                const locModal = bootstrap.Modal.getInstance(locationModalEl) || new bootstrap.Modal(locationModalEl);
                locModal.show();
                if (searchInput) {
                    searchInput.value = '';
                    renderLocationList('');
                    setTimeout(() => searchInput.focus(), 300);
                }
            }
        });
    }

    // Back button
    if (btnBack) {
        btnBack.addEventListener('click', function() {
            closeLocationModalAndReturn();
        });
    }

    // Real-time search filter
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            renderLocationList(this.value);
        });
    }

    // GPS Navigation Arrow Click
    if (btnGps) {
        btnGps.addEventListener('click', function() {
            if (navigator.geolocation) {
                btnGps.style.opacity = '0.5';
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        btnGps.style.opacity = '1';
                        // Detected position - default to TP. Hồ Chí Minh or exact city
                        selectLocation("TP. Hồ Chí Minh");
                    },
                    (err) => {
                        btnGps.style.opacity = '1';
                        selectLocation("TP. Hồ Chí Minh");
                    },
                    { timeout: 5000 }
                );
            } else {
                selectLocation("TP. Hồ Chí Minh");
            }
        });
    }

    function selectLocation(name) {
        const badge = document.getElementById('composerLocationBadge');
        const textSpan = document.getElementById('composerLocationText');
        const input = document.getElementById('modalLocationInput');

        if (textSpan) textSpan.textContent = name;
        if (input) input.value = name;
        if (badge) badge.style.display = 'inline-flex';

        // Trigger submit button check in composer
        if (typeof window.triggerComposerSubmitCheck === 'function') {
            window.triggerComposerSubmitCheck();
        }

        closeLocationModalAndReturn();
    }

    function closeLocationModalAndReturn() {
        if (window.bootstrap) {
            bootstrap.Modal.getInstance(locationModalEl)?.hide();
            if (newThreadModalEl) {
                const composerModal = bootstrap.Modal.getInstance(newThreadModalEl) || new bootstrap.Modal(newThreadModalEl);
                composerModal.show();
                setTimeout(() => {
                    document.getElementById('modalComposerText')?.focus();
                }, 250);
            }
        }
    }
});
</script>
