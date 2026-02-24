@extends('layouts.app')

@section('title', 'Test Date Format')
@section('page-title', 'Kiểm tra định dạng ngày tháng')
@section('page-description', 'Test xem ngày tháng có format đúng theo chuẩn Việt Nam không')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">
            <i class="fas fa-calendar-check text-blue-600 mr-2"></i>
            Test Định dạng Ngày Tháng
        </h2>

        <!-- Thông tin máy -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <h3 class="font-bold text-blue-800 mb-2">
                <i class="fas fa-info-circle mr-2"></i>Thông tin trình duyệt
            </h3>
            <div class="space-y-1 text-sm">
                <p><strong>User Agent:</strong> <span id="userAgent"></span></p>
                <p><strong>Timezone:</strong> <span id="timezone"></span></p>
                <p><strong>Locale:</strong> <span id="locale"></span></p>
                <p><strong>Language:</strong> <span id="language"></span></p>
            </div>
        </div>

        <!-- Test 1: Format ngày hiện tại -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-bold text-gray-800 mb-3">
                <i class="fas fa-calendar-day text-green-600 mr-2"></i>
                Test 1: Format ngày hiện tại
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">JavaScript thuần (theo máy):</p>
                    <p class="font-mono text-lg" id="nativeDate"></p>
                </div>
                <div class="bg-green-50 p-3 rounded border border-green-300">
                    <p class="text-xs text-gray-600 mb-1">Day.js (chuẩn Việt Nam):</p>
                    <p class="font-mono text-lg font-bold text-green-700" id="dayjsDate"></p>
                </div>
            </div>
        </div>

        <!-- Test 2: Format ngày giờ -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-bold text-gray-800 mb-3">
                <i class="fas fa-clock text-purple-600 mr-2"></i>
                Test 2: Format ngày giờ
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">JavaScript thuần:</p>
                    <p class="font-mono text-lg" id="nativeDateTime"></p>
                </div>
                <div class="bg-purple-50 p-3 rounded border border-purple-300">
                    <p class="text-xs text-gray-600 mb-1">Day.js (chuẩn Việt Nam):</p>
                    <p class="font-mono text-lg font-bold text-purple-700" id="dayjsDateTime"></p>
                </div>
            </div>
        </div>

        <!-- Test 3: Các hàm helper -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-bold text-gray-800 mb-3">
                <i class="fas fa-tools text-orange-600 mr-2"></i>
                Test 3: Các hàm helper
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Đầu tháng:</p>
                    <p class="font-mono text-sm font-bold" id="startOfMonth"></p>
                </div>
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Cuối tháng:</p>
                    <p class="font-mono text-sm font-bold" id="endOfMonth"></p>
                </div>
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Đầu năm:</p>
                    <p class="font-mono text-sm font-bold" id="startOfYear"></p>
                </div>
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Cuối năm:</p>
                    <p class="font-mono text-sm font-bold" id="endOfYear"></p>
                </div>
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Đầu tuần:</p>
                    <p class="font-mono text-sm font-bold" id="startOfWeek"></p>
                </div>
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Cuối tuần:</p>
                    <p class="font-mono text-sm font-bold" id="endOfWeek"></p>
                </div>
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Đầu quý:</p>
                    <p class="font-mono text-sm font-bold" id="startOfQuarter"></p>
                </div>
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Cuối quý:</p>
                    <p class="font-mono text-sm font-bold" id="endOfQuarter"></p>
                </div>
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Tháng trước:</p>
                    <p class="font-mono text-sm font-bold" id="lastMonth"></p>
                </div>
            </div>
        </div>

        <!-- Test 4: Tên ngày/tháng tiếng Việt -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-bold text-gray-800 mb-3">
                <i class="fas fa-language text-red-600 mr-2"></i>
                Test 4: Tên ngày/tháng tiếng Việt
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Tên ngày trong tuần:</p>
                    <p class="font-mono text-lg font-bold text-red-700" id="dayName"></p>
                </div>
                <div class="bg-white p-3 rounded border">
                    <p class="text-xs text-gray-600 mb-1">Tên tháng:</p>
                    <p class="font-mono text-lg font-bold text-red-700" id="monthName"></p>
                </div>
            </div>
        </div>

        <!-- Test 5: Format cho input -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-bold text-gray-800 mb-3">
                <i class="fas fa-keyboard text-indigo-600 mr-2"></i>
                Test 5: Format cho input type="date"
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Input với giá trị từ helper:</label>
                    <input type="date" id="testInput" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="bg-indigo-50 p-3 rounded border border-indigo-300">
                    <p class="text-xs text-gray-600 mb-1">Giá trị đã set (yyyy-mm-dd):</p>
                    <p class="font-mono text-lg font-bold text-indigo-700" id="inputValue"></p>
                </div>
            </div>
        </div>

        <!-- Kết luận -->
        <div class="bg-green-50 border-l-4 border-green-500 p-4">
            <h3 class="font-bold text-green-800 mb-2">
                <i class="fas fa-check-circle mr-2"></i>Kết luận
            </h3>
            <p class="text-sm text-green-700">
                Nếu tất cả các ngày tháng ở cột <strong>Day.js (chuẩn Việt Nam)</strong> đều hiển thị đúng định dạng 
                <strong>dd/mm/yyyy</strong> (ví dụ: 24/02/2026) thì hệ thống đã hoạt động đúng, 
                không phụ thuộc vào cài đặt máy người dùng.
            </p>
        </div>

        <!-- Nút test lại -->
        <div class="mt-6 text-center">
            <button onclick="runTests()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-sync-alt mr-2"></i>Chạy lại test
            </button>
        </div>
    </div>
</div>

<script>
function runTests() {
    const now = new Date();
    
    // Thông tin trình duyệt
    document.getElementById('userAgent').textContent = navigator.userAgent;
    document.getElementById('timezone').textContent = Intl.DateTimeFormat().resolvedOptions().timeZone;
    document.getElementById('locale').textContent = navigator.language;
    document.getElementById('language').textContent = navigator.languages.join(', ');
    
    // Test 1: Format ngày hiện tại
    document.getElementById('nativeDate').textContent = now.toLocaleDateString();
    document.getElementById('dayjsDate').textContent = formatDateVN(now);
    
    // Test 2: Format ngày giờ
    document.getElementById('nativeDateTime').textContent = now.toLocaleString();
    document.getElementById('dayjsDateTime').textContent = formatDateTimeVN(now);
    
    // Test 3: Các hàm helper
    document.getElementById('startOfMonth').textContent = formatDateVN(getStartOfMonth(now));
    document.getElementById('endOfMonth').textContent = formatDateVN(getEndOfMonth(now));
    document.getElementById('startOfYear').textContent = formatDateVN(getStartOfYear(now));
    document.getElementById('endOfYear').textContent = formatDateVN(getEndOfYear(now));
    document.getElementById('startOfWeek').textContent = formatDateVN(getStartOfWeek(now));
    document.getElementById('endOfWeek').textContent = formatDateVN(getEndOfWeek(now));
    document.getElementById('startOfQuarter').textContent = formatDateVN(getStartOfQuarter(now));
    document.getElementById('endOfQuarter').textContent = formatDateVN(getEndOfQuarter(now));
    document.getElementById('lastMonth').textContent = formatDateVN(subtractMonths(now, 1));
    
    // Test 4: Tên ngày/tháng tiếng Việt
    document.getElementById('dayName').textContent = getDayNameVN(now);
    document.getElementById('monthName').textContent = getMonthNameVN(now);
    
    // Test 5: Format cho input
    const inputValue = formatDateForInput(now);
    document.getElementById('testInput').value = inputValue;
    document.getElementById('inputValue').textContent = inputValue;
}

// Chạy test khi trang load
window.addEventListener('load', runTests);
</script>
@endsection
