export function setupLocationGps(doc = document, win = window) {
    const modal = doc.getElementById('locationPickerModal');
    const button = doc.getElementById('btnLocationGPS');
    const status = doc.getElementById('locationPickerStatus');
    if (!modal || !button || !status) return;

    let activeRequest = null;
    const setBusy = busy => {
        button.disabled = busy;
        button.setAttribute('aria-busy', String(busy));
    };
    modal.addEventListener('hide.bs.modal', () => {
        activeRequest?.controller.abort();
        activeRequest = null;
        setBusy(false);
        status.textContent = '';
    });

    button.addEventListener('click', async () => {
        if (activeRequest) return;
        if (!win.isSecureContext) {
            status.textContent = 'GPS cần kết nối HTTPS hoặc localhost. Hãy mở website bằng HTTPS rồi thử lại.';
            return;
        }
        if (!win.navigator.geolocation) {
            status.textContent = 'Trình duyệt này không hỗ trợ lấy vị trí. Bạn vẫn có thể chọn địa điểm trong danh sách.';
            return;
        }

        const request = { controller: new win.AbortController() };
        activeRequest = request;
        let lookupTimer;
        let locating = true;
        setBusy(true);
        status.textContent = 'Đang lấy vị trí hiện tại. Hãy cho phép trình duyệt truy cập vị trí.';
        try {
            const position = await new Promise((resolve, reject) => {
                win.navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true, timeout: 15000, maximumAge: 0,
                });
            });
            if (activeRequest !== request) return;
            const { latitude, longitude } = position.coords;
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || Math.abs(latitude) > 90 || Math.abs(longitude) > 180) {
                throw new Error('Invalid coordinates');
            }
            locating = false;
            status.textContent = 'Đang tra tên khu vực hiện tại…';
            const url = new URL('https://api.bigdatacloud.net/data/reverse-geocode-client');
            url.search = new URLSearchParams({ latitude, longitude, localityLanguage: 'vi' }).toString();
            lookupTimer = win.setTimeout(() => request.controller.abort(), 10000);
            const response = await win.fetch(url, {
                signal: request.controller.signal, credentials: 'omit', cache: 'no-store', referrerPolicy: 'no-referrer',
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Location lookup failed');
            const data = await response.json();
            if (activeRequest !== request) return;
            const areas = [data?.locality, data?.city, data?.principalSubdivision]
                .filter(value => typeof value === 'string' && value.trim()).map(value => value.trim());
            if (data?.error || !areas.length) throw new Error('Location not found');
            if (typeof data.countryName === 'string' && data.countryName.trim()) areas.push(data.countryName.trim());
            const uniqueAreas = areas.filter((area, index) => areas.findIndex(value => value.toLocaleLowerCase('vi') === area.toLocaleLowerCase('vi')) === index);
            const name = Array.from(uniqueAreas.join(', ')).slice(0, 150).join('');
            modal.dispatchEvent(new win.CustomEvent('threads:location-selected', { detail: { name } }));
            status.textContent = '';
        } catch (error) {
            if (activeRequest !== request) return;
            const messages = {
                1: 'Bạn chưa cho phép truy cập vị trí. Hãy bật quyền vị trí cho website trong trình duyệt rồi thử lại.',
                2: 'Thiết bị chưa xác định được vị trí. Hãy bật dịch vụ vị trí/GPS rồi thử lại.',
                3: 'Lấy vị trí quá lâu. Hãy kiểm tra GPS và thử lại.',
            };
            status.textContent = locating
                ? messages[error?.code] || 'Không lấy được tọa độ hiện tại. Hãy kiểm tra GPS và thử lại.'
                : 'Không tra được tên khu vực. Hãy kiểm tra kết nối mạng rồi thử lại hoặc chọn địa điểm trong danh sách.';
        } finally {
            win.clearTimeout(lookupTimer);
            if (activeRequest === request) {
                activeRequest = null;
                setBusy(false);
            }
        }
    });
}
