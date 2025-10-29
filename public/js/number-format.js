// Format number with thousand separators for VND
function formatVND(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if (value) {
        input.value = parseInt(value).toLocaleString('en-US');
    }
}

// Format USD with decimal
function formatUSD(input) {
    let value = input.value.replace(/[^\d.]/g, '');
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    if (parts[1] && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].substring(0, 2);
    }
    if (value) {
        const num = parseFloat(value);
        if (!isNaN(num)) {
            input.value = num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }
}

// Parse formatted number back to plain number
function unformatNumber(value) {
    if (typeof value === 'string') {
        return value.replace(/[^\d.]/g, '');
    }
    return value;
}
