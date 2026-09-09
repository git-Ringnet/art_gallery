// Format number with thousand separators for VND
function formatVND(input, isBlur = false) {
    let value = input.value.replace(/[^\d]/g, '');
    if (value) {
        const num = parseInt(value);
        const MAX_VND = 999999999999; // 999 tỷ VND (decimal 15,2)

        if (num > MAX_VND) {
            input.classList.add('border-red-500', 'bg-red-50');
            input.title = 'Giá trị quá lớn! Tối đa: ' + MAX_VND.toLocaleString('en-US') + ' VND';
            // Cắt về giá trị tối đa
            input.value = MAX_VND.toLocaleString('en-US');

            // Hiển thị cảnh báo
            showWarning(input, 'Giá VND vượt quá giới hạn! Tối đa: ' + MAX_VND.toLocaleString('en-US') + ' VND');
        } else {
            input.classList.remove('border-red-500', 'bg-red-50');
            input.title = '';
            input.value = num.toLocaleString('en-US');
        }
    } else if (isBlur) {
        input.value = '0';
    }
}

// Format USD as whole number (No decimals)
function formatUSD(input, isBlur = false) {
    // Lưu vị trí con trỏ
    const cursorPosition = input.selectionStart;
    const oldValue = input.value;

    // Lấy giá trị trước con trỏ để tính toán vị trí mới
    const valueBeforeCursor = oldValue.substring(0, cursorPosition);
    const digitsBeforeCursor = valueBeforeCursor.replace(/[^\d]/g, '').length;

    // Format giá trị (loại bỏ tất cả trừ số)
    let value = input.value.replace(/[^\d]/g, '');

    if (value) {
        const num = parseInt(value);
        const MAX_USD = 99999999; // 99 triệu USD

        if (!isNaN(num)) {
            if (num > MAX_USD) {
                input.classList.add('border-red-500', 'bg-red-50');
                input.title = 'Giá trị quá lớn! Tối đa: $' + MAX_USD.toLocaleString('en-US');
                // Cắt về giá trị tối đa
                const formatted = MAX_USD.toLocaleString('en-US');
                input.value = formatted;

                // Hiển thị cảnh báo
                showWarning(input, 'Giá USD vượt quá giới hạn! Tối đa: $' + MAX_USD.toLocaleString('en-US'));
            } else {
                input.classList.remove('border-red-500', 'bg-red-50');
                input.title = '';
                const formatted = num.toLocaleString('en-US');
                input.value = formatted;

                // Tính toán vị trí con trỏ mới
                if (!isBlur) {
                    let newCursorPosition = 0;
                    let digitCount = 0;
                    for (let i = 0; i < formatted.length; i++) {
                        if (/\d/.test(formatted[i])) {
                            digitCount++;
                        }
                        if (digitCount >= digitsBeforeCursor) {
                            newCursorPosition = i + 1;
                            break;
                        }
                    }
                    // Khôi phục vị trí con trỏ
                    input.setSelectionRange(newCursorPosition, newCursorPosition);
                }
            }
        }
    } else if (isBlur) {
        input.value = '0';
    }
}

// Format VND cho input thanh toán (không giới hạn)
function formatPaymentVND(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if (value) {
        input.value = parseInt(value).toLocaleString('en-US');
    }
}

// Validate số tiền thanh toán sales - gọi calcDebt để tính toán và validate
function validateSalesPayment(input) {
    // Format số trước
    formatPaymentVND(input);

    // Gọi calcDebt để tính toán và validate
    if (typeof calcDebt === 'function') {
        calcDebt();
    }
}

// Hiển thị cảnh báo tạm thời
function showWarning(input, message) {
    // Xóa cảnh báo cũ nếu có
    const oldWarning = input.parentElement.querySelector('.price-warning');
    if (oldWarning) {
        oldWarning.remove();
    }

    // Tạo cảnh báo mới
    const warning = document.createElement('div');
    warning.className = 'price-warning absolute z-50 bg-red-500 text-white text-xs px-3 py-2 rounded-lg shadow-lg mt-1 animate-pulse';
    warning.style.top = (input.offsetTop + input.offsetHeight) + 'px';
    warning.style.left = input.offsetLeft + 'px';
    warning.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>' + message;

    // Thêm vào DOM
    input.parentElement.style.position = 'relative';
    input.parentElement.appendChild(warning);

    // Tự động xóa sau 3 giây
    setTimeout(() => {
        warning.remove();
    }, 3000);
}

// Parse formatted number back to plain number
function unformatNumber(value) {
    if (value === null || value === undefined) return '0';
    if (typeof value === 'number') return value.toString();
    if (typeof value !== 'string') return String(value || '0');

    let str = value.trim();
    if (!str) return '0';

    // 1. Nếu có ký hiệu VND (đ, ₫) -> luôn là số nguyên VND
    if (/[đ₫]/i.test(str)) {
        return str.replace(/[^\d]/g, '') || '0';
    }

    // 2. Loại bỏ ký hiệu $ và khoảng trắng
    let clean = str.replace(/[$]/g, '').trim();

    // 3. Nếu có cả dấu phẩy và dấu chấm (ví dụ "4,064.60" hoặc "4.064,60")
    if (clean.includes(',') && clean.includes('.')) {
        if (clean.lastIndexOf('.') > clean.lastIndexOf(',')) {
            // Chuẩn US: "4,064.60" -> xóa phẩy, giữ chấm
            return clean.replace(/,/g, '');
        } else {
            // Chuẩn EU/VN: "4.064,60" -> xóa chấm, đổi phẩy thành chấm
            return clean.replace(/\./g, '').replace(',', '.');
        }
    }

    // 4. Nếu chỉ có dấu phẩy (không có chấm)
    if (clean.includes(',')) {
        const parts = clean.split(',');
        // Nếu có dạng "0,5" hoặc "4064,6" (1 dấu phẩy và phần sau có 1-2 chữ số) -> dấu phẩy thập phân
        if (parts.length === 2 && parts[1].length <= 2 && parts[1].length > 0 && parts[0].length <= 4) {
            return parts[0] + '.' + parts[1];
        }
        // Phân cách hàng nghìn (ví dụ "26,000", "1,680,000", "4,000")
        return clean.replace(/,/g, '');
    }

    // 5. Nếu chỉ có dấu chấm (không có phẩy)
    if (clean.includes('.')) {
        const parts = clean.split('.');
        // Nếu có nhiều dấu chấm (ví dụ "1.680.000") -> phân cách hàng nghìn
        if (parts.length > 2) {
            return clean.replace(/\./g, '');
        }
        // Nếu có 1 dấu chấm:
        // Nếu phần sau dấu chấm có đúng 3 chữ số (ví dụ "26.000", "100.000") -> phân cách hàng nghìn
        if (parts[1].length === 3) {
            return clean.replace(/\./g, '');
        }
        // Nếu có 1 hoặc 2 chữ số thập phân (ví dụ "4064.6", "4064.60", "0.5") -> giữ nguyên dấu chấm thập phân
        return clean;
    }

    // 6. Số nguyên thuần túy hoặc dạng khác
    return clean.replace(/[^\d.]/g, '') || '0';
}
