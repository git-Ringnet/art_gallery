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
    if (typeof value === 'string') {
        // Nếu là VND (có ký hiệu đ) hoặc có nhiều hơn 1 dấu chấm, loại bỏ tất cả dấu chấm (phân cách hàng nghìn)
        if (value.includes('đ') || (value.match(/\./g) || []).length > 1) {
            return value.replace(/[^\d]/g, '') || '0';
        }
        // Trường hợp USD: giữ lại dấu chấm duy nhất làm số thập phân
        let val = value.replace(/[^\d.]/g, '');
        return val || '0';
    }
    return value || '0';
}
