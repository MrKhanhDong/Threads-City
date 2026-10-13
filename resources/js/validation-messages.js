export function nativeValidationMessage(control) {
    const label = control.labels?.[0]?.textContent?.trim() || 'Thông tin này';
    const validity = control.validity;
    if (validity.valueMissing) {
        return control.type === 'checkbox' ? 'Vui lòng tích ô xác nhận trước khi tiếp tục.' : 'Vui lòng nhập hoặc chọn ' + label + '.';
    }
    if (validity.typeMismatch) {
        return control.type === 'email' ? label + ' không đúng định dạng. Ví dụ: ten@gmail.com.' : label + ' phải là địa chỉ hợp lệ bắt đầu bằng http:// hoặc https://.';
    }
    if (validity.tooShort) return label + ' phải có ít nhất ' + control.minLength + ' ký tự.';
    if (validity.tooLong) return label + ' không được dài quá ' + control.maxLength + ' ký tự.';
    if (validity.rangeUnderflow) return label + ' phải lớn hơn hoặc bằng ' + control.min + '.';
    if (validity.rangeOverflow) return label + ' không được lớn hơn ' + control.max + '.';
    if (validity.badInput || validity.stepMismatch) return label + ' phải là một số đúng định dạng yêu cầu.';
    if (validity.patternMismatch) return label + ' không đúng định dạng yêu cầu.';
    if (!validity.valid) return label + ' chưa hợp lệ. Hãy kiểm tra và nhập lại.';
    if (control.name === 'password_confirmation' && control.value && control.value !== control.form?.elements.namedItem('password')?.value) {
        return 'Mật khẩu xác nhận không khớp với mật khẩu đã nhập. Hãy nhập lại hai ô giống nhau.';
    }
    return '';
}

export function requestErrorMessage(error, fallback) {
    if (error?.name === 'SyntaxError') return 'Phản hồi từ máy chủ không đúng định dạng. Hãy tải lại trang rồi thử lại.';
    if (/failed to fetch|networkerror|network error|load failed/i.test(error?.message || '')) {
        return 'Không kết nối được máy chủ. Hãy kiểm tra kết nối mạng rồi thử lại.';
    }
    const message = typeof error === 'string' ? error : error?.message;
    return typeof message === 'string' && /[À-ỹ]/u.test(message) ? message : fallback;
}

export function setupVietnameseValidation(doc = document) {
    doc.addEventListener('invalid', event => {
        const control = event.target;
        if (typeof control.setCustomValidity !== 'function') return;
        control.setCustomValidity('');
        control.setCustomValidity(nativeValidationMessage(control));
    }, true);
    const clearMessage = event => {
        const control = event.target;
        if (typeof control.setCustomValidity !== 'function') return;
        control.setCustomValidity('');
        if (control.name === 'password' || control.name === 'password_confirmation') {
            const confirmation = control.form?.elements.namedItem('password_confirmation');
            if (confirmation) {
                confirmation.setCustomValidity('');
                if (confirmation.value) confirmation.setCustomValidity(nativeValidationMessage(confirmation));
            }
        }
    };
    doc.addEventListener('input', clearMessage);
    doc.addEventListener('change', clearMessage);
}
