/**
 * Date Helper - Sử dụng Day.js để format ngày tháng theo chuẩn Việt Nam
 * Docs: https://day.js.org/
 */

// Khởi tạo Day.js với locale tiếng Việt
dayjs.locale('vi');

/**
 * Format ngày theo định dạng dd/mm/yyyy
 * @param {Date|string} date - Ngày cần format
 * @returns {string} - Ngày đã format theo dd/mm/yyyy
 */
function formatDateVN(date) {
    return dayjs(date).format('DD/MM/YYYY');
}

/**
 * Format ngày giờ theo định dạng dd/mm/yyyy HH:mm
 * @param {Date|string} date - Ngày giờ cần format
 * @returns {string} - Ngày giờ đã format
 */
function formatDateTimeVN(date) {
    return dayjs(date).format('DD/MM/YYYY HH:mm');
}

/**
 * Format ngày cho input type="date" (yyyy-mm-dd)
 * @param {Date|string} date - Ngày cần format
 * @returns {string} - Ngày đã format theo yyyy-mm-dd
 */
function formatDateForInput(date) {
    return dayjs(date).format('YYYY-MM-DD');
}

/**
 * Parse ngày từ string dd/mm/yyyy sang Date object
 * @param {string} dateStr - String ngày theo format dd/mm/yyyy
 * @returns {Date} - Date object
 */
function parseDateVN(dateStr) {
    return dayjs(dateStr, 'DD/MM/YYYY').toDate();
}

/**
 * Lấy ngày đầu tháng
 * @param {Date} date - Ngày bất kỳ trong tháng
 * @returns {Date} - Ngày đầu tháng
 */
function getStartOfMonth(date = new Date()) {
    return dayjs(date).startOf('month').toDate();
}

/**
 * Lấy ngày cuối tháng
 * @param {Date} date - Ngày bất kỳ trong tháng
 * @returns {Date} - Ngày cuối tháng
 */
function getEndOfMonth(date = new Date()) {
    return dayjs(date).endOf('month').toDate();
}

/**
 * Lấy ngày đầu năm
 * @param {Date} date - Ngày bất kỳ trong năm
 * @returns {Date} - Ngày đầu năm
 */
function getStartOfYear(date = new Date()) {
    return dayjs(date).startOf('year').toDate();
}

/**
 * Lấy ngày cuối năm
 * @param {Date} date - Ngày bất kỳ trong năm
 * @returns {Date} - Ngày cuối năm
 */
function getEndOfYear(date = new Date()) {
    return dayjs(date).endOf('year').toDate();
}

/**
 * Lấy ngày đầu tuần (Thứ 2)
 * @param {Date} date - Ngày bất kỳ trong tuần
 * @returns {Date} - Ngày đầu tuần
 */
function getStartOfWeek(date = new Date()) {
    return dayjs(date).startOf('week').toDate();
}

/**
 * Lấy ngày cuối tuần (Chủ nhật)
 * @param {Date} date - Ngày bất kỳ trong tuần
 * @returns {Date} - Ngày cuối tuần
 */
function getEndOfWeek(date = new Date()) {
    return dayjs(date).endOf('week').toDate();
}

/**
 * Lấy ngày đầu quý
 * @param {Date} date - Ngày bất kỳ trong quý
 * @returns {Date} - Ngày đầu quý
 */
function getStartOfQuarter(date = new Date()) {
    return dayjs(date).startOf('quarter').toDate();
}

/**
 * Lấy ngày cuối quý
 * @param {Date} date - Ngày bất kỳ trong quý
 * @returns {Date} - Ngày cuối quý
 */
function getEndOfQuarter(date = new Date()) {
    return dayjs(date).endOf('quarter').toDate();
}

/**
 * Thêm số ngày vào một ngày
 * @param {Date} date - Ngày gốc
 * @param {number} days - Số ngày cần thêm
 * @returns {Date} - Ngày sau khi thêm
 */
function addDays(date, days) {
    return dayjs(date).add(days, 'day').toDate();
}

/**
 * Trừ số ngày từ một ngày
 * @param {Date} date - Ngày gốc
 * @param {number} days - Số ngày cần trừ
 * @returns {Date} - Ngày sau khi trừ
 */
function subtractDays(date, days) {
    return dayjs(date).subtract(days, 'day').toDate();
}

/**
 * Thêm số tháng vào một ngày
 * @param {Date} date - Ngày gốc
 * @param {number} months - Số tháng cần thêm
 * @returns {Date} - Ngày sau khi thêm
 */
function addMonths(date, months) {
    return dayjs(date).add(months, 'month').toDate();
}

/**
 * Trừ số tháng từ một ngày
 * @param {Date} date - Ngày gốc
 * @param {number} months - Số tháng cần trừ
 * @returns {Date} - Ngày sau khi trừ
 */
function subtractMonths(date, months) {
    return dayjs(date).subtract(months, 'month').toDate();
}

/**
 * So sánh hai ngày
 * @param {Date} date1 - Ngày thứ nhất
 * @param {Date} date2 - Ngày thứ hai
 * @returns {boolean} - true nếu date1 sau date2
 */
function isAfter(date1, date2) {
    return dayjs(date1).isAfter(dayjs(date2));
}

/**
 * So sánh hai ngày
 * @param {Date} date1 - Ngày thứ nhất
 * @param {Date} date2 - Ngày thứ hai
 * @returns {boolean} - true nếu date1 trước date2
 */
function isBefore(date1, date2) {
    return dayjs(date1).isBefore(dayjs(date2));
}

/**
 * Kiểm tra hai ngày có cùng ngày không
 * @param {Date} date1 - Ngày thứ nhất
 * @param {Date} date2 - Ngày thứ hai
 * @returns {boolean} - true nếu cùng ngày
 */
function isSameDay(date1, date2) {
    return dayjs(date1).isSame(dayjs(date2), 'day');
}

/**
 * Lấy tên tháng tiếng Việt
 * @param {Date} date - Ngày
 * @returns {string} - Tên tháng (VD: "Tháng 1", "Tháng 2")
 */
function getMonthNameVN(date) {
    return 'Tháng ' + dayjs(date).format('M');
}

/**
 * Lấy tên ngày trong tuần tiếng Việt
 * @param {Date} date - Ngày
 * @returns {string} - Tên ngày (VD: "Thứ 2", "Thứ 3", "Chủ nhật")
 */
function getDayNameVN(date) {
    const dayNames = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
    return dayNames[dayjs(date).day()];
}

/**
 * Format ngày theo định dạng tùy chỉnh
 * @param {Date|string} date - Ngày cần format
 * @param {string} format - Định dạng (VD: 'DD/MM/YYYY', 'DD-MM-YYYY HH:mm:ss')
 * @returns {string} - Ngày đã format
 */
function formatCustom(date, format) {
    return dayjs(date).format(format);
}
