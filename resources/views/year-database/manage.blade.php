@extends('layouts.app')

@section('title', 'Quản lý Database theo Năm')
@section('page-title', 'Quản lý Database theo Năm')
@section('page-description', 'Export, Cleanup và Chuẩn bị năm mới')

@section('content')
<div class="p-4 fade-in">
    <!-- Thông tin năm hiện tại -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-500">
            <h5 class="text-gray-700 text-xs font-semibold mb-1">
                <i class="fas fa-calendar-check mr-1"></i>Năm Hiện Tại
            </h5>
            <h2 class="text-2xl font-bold text-blue-600">{{ $currentYear->year ?? date('Y') }}</h2>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500">
            <h5 class="text-gray-700 text-xs font-semibold mb-1">
                <i class="fas fa-database mr-1"></i>Số Năm Có Sẵn
            </h5>
            <h2 class="text-2xl font-bold text-green-600">{{ $allYears->count() }}</h2>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-purple-500">
            <h5 class="text-gray-700 text-xs font-semibold mb-1">
                <i class="fas fa-file-archive mr-1"></i>File Backup
            </h5>
            <h2 class="text-2xl font-bold text-purple-600">{{ $totalExports }}</h2>
        </div>
    </div>

    <!-- Quy trình cuối năm -->
    <div class="bg-white rounded-lg shadow-md mb-4">
        <div class="bg-gradient-to-r from-orange-500 to-red-500 px-4 py-3 rounded-t-lg">
            <h5 class="text-white font-semibold text-sm">
                <i class="fas fa-calendar-alt mr-1"></i>Quy Trình Cuối Năm
            </h5>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Bước 1: Export -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-green-600 font-bold">1</span>
                        </div>
                        <h6 class="font-semibold text-gray-800">Export Backup</h6>
                    </div>
                    <p class="text-xs text-gray-600 mb-3">Tạo file backup đầy đủ (SQL + ảnh) trước khi xóa dữ liệu cũ</p>
                    @hasPermission('year_database', 'can_export')
                    <button onclick="openExportFullModal()" class="w-full px-3 py-2 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-download mr-1"></i>Export với Ảnh
                    </button>
                    @endhasPermission
                </div>

                <!-- Bước 2: Cleanup -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-red-600 font-bold">2</span>
                        </div>
                        <h6 class="font-semibold text-gray-800">Xóa Dữ Liệu Cũ</h6>
                    </div>
                    <p class="text-xs text-gray-600 mb-3">Xóa giao dịch năm cũ, giữ lại tồn đầu kỳ và ảnh sản phẩm còn tồn</p>
                    @hasPermission('year_database', 'can_delete')
                    <button onclick="openCleanupModal()" class="w-full px-3 py-2 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700">
                        <i class="fas fa-trash mr-1"></i>Cleanup Năm Cũ
                    </button>
                    @endhasPermission
                </div>

                <!-- Bước 3: Năm mới -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-blue-600 font-bold">3</span>
                        </div>
                        <h6 class="font-semibold text-gray-800">Chuẩn Bị Năm Mới</h6>
                    </div>
                    <p class="text-xs text-gray-600 mb-3">Tạo năm mới và set làm năm hiện tại</p>
                    @hasPermission('year_database', 'can_create')
                    <button onclick="openPrepareModal()" class="w-full px-3 py-2 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus mr-1"></i>Tạo Năm Mới
                    </button>
                    @endhasPermission
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách năm -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-4 py-3">
            <h5 class="text-white font-semibold text-sm">
                <i class="fas fa-list mr-1"></i>Danh Sách Năm
            </h5>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Năm</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Trạng Thái</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mô Tả</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($allYears as $year)
                    <tr class="{{ $year->is_active ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                        <td class="px-3 py-2">
                            <span class="font-bold text-gray-900">{{ $year->year }}</span>
                            @if($year->is_active)
                            <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">Hiện tại</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($year->is_on_server)
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">
                                <i class="fas fa-check-circle mr-1"></i>Online
                            </span>
                            @else
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">
                                <i class="fas fa-archive mr-1"></i>Offline
                            </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600">{{ $year->description ?: '-' }}</td>
                        <td class="px-3 py-2">
                            <div class="flex space-x-1">
                                <button onclick="viewYearStats({{ $year->year }})" 
                                    class="px-2 py-1 bg-blue-100 text-blue-600 rounded text-xs hover:bg-blue-200">
                                    <i class="fas fa-chart-bar"></i>
                                </button>
                                @if(!$year->is_active)
                                <button onclick="confirmCleanup({{ $year->year }})" 
                                    class="px-2 py-1 bg-red-100 text-red-600 rounded text-xs hover:bg-red-200">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Export Full -->
<div id="exportFullModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-4 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-semibold text-gray-900">Export Database với Ảnh</h3>
            <button onclick="closeModal('exportFullModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="exportFullForm">
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Chọn năm</label>
                <select name="year" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md">
                    @foreach($allYears as $year)
                    <option value="{{ $year->year }}">{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800">
                <i class="fas fa-info-circle mr-1"></i>
                File ZIP sẽ bao gồm: SQL dump + thư mục ảnh sản phẩm
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('exportFullModal')" class="px-3 py-1.5 text-xs bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Hủy</button>
                <button type="button" onclick="handleExportFull()" class="px-3 py-1.5 text-xs bg-green-600 text-white rounded-md hover:bg-green-700">
                    <i class="fas fa-download mr-1"></i>Export
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cleanup -->
<div id="cleanupModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-4 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-semibold text-gray-900">Xóa Dữ Liệu Năm Cũ</h3>
            <button onclick="closeModal('cleanupModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="cleanupForm">
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Chọn năm cần xóa</label>
                <select name="year" id="cleanup_year" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md" onchange="loadYearStats()">
                    @foreach($allYears->where('is_active', false) as $year)
                    <option value="{{ $year->year }}">{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div id="cleanup_stats" class="mb-3 p-2 bg-gray-50 border border-gray-200 rounded text-xs">
                <p class="text-gray-600">Chọn năm để xem thống kê...</p>
            </div>
            <div class="mb-3">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="keep_images" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                    <span class="ml-2 text-xs text-gray-700">Giữ lại ảnh (không xóa ảnh sản phẩm)</span>
                </label>
            </div>
            <div class="mb-3 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-800">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>CẢNH BÁO:</strong> Thao tác này không thể hoàn tác! Hãy export backup trước.
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('cleanupModal')" class="px-3 py-1.5 text-xs bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Hủy</button>
                <button type="button" onclick="handleCleanup()" class="px-3 py-1.5 text-xs bg-red-600 text-white rounded-md hover:bg-red-700">
                    <i class="fas fa-trash mr-1"></i>Xóa Dữ Liệu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Prepare New Year -->
<div id="prepareModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-4 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-semibold text-gray-900">Chuẩn Bị Năm Mới</h3>
            <button onclick="closeModal('prepareModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="prepareForm">
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Năm mới</label>
                <input type="number" name="year" value="{{ date('Y') + 1 }}" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md">
            </div>
            <div class="mb-3 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800">
                <i class="fas fa-info-circle mr-1"></i>
                Năm mới sẽ được set làm năm hiện tại. Tồn kho hiện tại sẽ là tồn đầu kỳ.
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('prepareModal')" class="px-3 py-1.5 text-xs bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Hủy</button>
                <button type="button" onclick="handlePrepare()" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-plus mr-1"></i>Tạo Năm Mới
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Stats -->
<div id="statsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-4 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-semibold text-gray-900">Thống Kê Năm <span id="stats_year"></span></h3>
            <button onclick="closeModal('statsModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="stats_content" class="text-xs">
            <p class="text-gray-600">Đang tải...</p>
        </div>
        <div class="mt-3 flex justify-end">
            <button type="button" onclick="closeModal('statsModal')" class="px-3 py-1.5 text-xs bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Đóng</button>
        </div>
    </div>
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function openExportFullModal() {
    openModal('exportFullModal');
}

function openCleanupModal() {
    openModal('cleanupModal');
    loadYearStats();
}

function openPrepareModal() {
    openModal('prepareModal');
}

async function loadYearStats() {
    const year = document.getElementById('cleanup_year').value;
    const statsDiv = document.getElementById('cleanup_stats');
    
    if (!year) {
        statsDiv.innerHTML = '<p class="text-gray-600">Chọn năm để xem thống kê...</p>';
        return;
    }

    statsDiv.innerHTML = '<p class="text-gray-600"><i class="fas fa-spinner fa-spin mr-1"></i>Đang tải...</p>';

    try {
        const response = await fetch(`/year/stats/${year}`);
        const data = await response.json();

        if (data.success) {
            const stats = data.stats;
            statsDiv.innerHTML = `
                <p class="font-semibold text-gray-700 mb-2">Dữ liệu sẽ bị xóa:</p>
                <ul class="space-y-1 text-gray-600">
                    <li>• Hóa đơn: ${stats.sales.toLocaleString()}</li>
                    <li>• Công nợ: ${stats.debts.toLocaleString()}</li>
                    <li>• Thanh toán: ${stats.payments.toLocaleString()}</li>
                    <li>• Đổi/Trả: ${stats.returns.toLocaleString()}</li>
                    <li>• Giao dịch kho: ${stats.inventory_transactions.toLocaleString()}</li>
                    <li>• Ảnh (sản phẩm đã bán hết): ${stats.images_to_delete}</li>
                </ul>
            `;
        }
    } catch (error) {
        statsDiv.innerHTML = '<p class="text-red-600">Lỗi khi tải thống kê</p>';
    }
}

async function viewYearStats(year) {
    document.getElementById('stats_year').textContent = year;
    document.getElementById('stats_content').innerHTML = '<p class="text-gray-600"><i class="fas fa-spinner fa-spin mr-1"></i>Đang tải...</p>';
    openModal('statsModal');

    try {
        const response = await fetch(`/year/stats/${year}`);
        const data = await response.json();

        if (data.success) {
            const stats = data.stats;
            document.getElementById('stats_content').innerHTML = `
                <div class="space-y-2">
                    <div class="flex justify-between py-1 border-b">
                        <span class="text-gray-600">Hóa đơn:</span>
                        <span class="font-semibold">${stats.sales.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b">
                        <span class="text-gray-600">Công nợ:</span>
                        <span class="font-semibold">${stats.debts.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b">
                        <span class="text-gray-600">Thanh toán:</span>
                        <span class="font-semibold">${stats.payments.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b">
                        <span class="text-gray-600">Đổi/Trả:</span>
                        <span class="font-semibold">${stats.returns.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b">
                        <span class="text-gray-600">Giao dịch kho:</span>
                        <span class="font-semibold">${stats.inventory_transactions.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-600">Ảnh (SP đã bán hết):</span>
                        <span class="font-semibold">${stats.images_to_delete}</span>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('stats_content').innerHTML = '<p class="text-red-600">Lỗi khi tải thống kê</p>';
    }
}

async function handleExportFull() {
    const form = document.getElementById('exportFullForm');
    const formData = new FormData(form);

    if (!confirm('Xác nhận export database với ảnh?')) return;

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang export...';

    try {
        const response = await fetch('{{ route("year.export.with-images") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert(data.message);
            closeModal('exportFullModal');
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Có lỗi xảy ra');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download mr-1"></i>Export';
    }
}

async function handleCleanup() {
    const form = document.getElementById('cleanupForm');
    const formData = new FormData(form);
    const year = formData.get('year');

    if (!confirm(`⚠️ CẢNH BÁO: Bạn sắp xóa TOÀN BỘ dữ liệu năm ${year}!\n\nThao tác này KHÔNG THỂ hoàn tác.\n\nBạn có chắc chắn?`)) return;

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang xóa...';

    try {
        const response = await fetch('{{ route("year.cleanup") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert(data.message);
            closeModal('cleanupModal');
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Có lỗi xảy ra');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash mr-1"></i>Xóa Dữ Liệu';
    }
}

async function handlePrepare() {
    const form = document.getElementById('prepareForm');
    const formData = new FormData(form);
    const year = formData.get('year');

    if (!confirm(`Xác nhận tạo năm ${year} và set làm năm hiện tại?`)) return;

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang tạo...';

    try {
        const response = await fetch('{{ route("year.prepare") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert(data.message);
            closeModal('prepareModal');
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Có lỗi xảy ra');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus mr-1"></i>Tạo Năm Mới';
    }
}

function confirmCleanup(year) {
    document.getElementById('cleanup_year').value = year;
    openCleanupModal();
}
</script>
@endsection
