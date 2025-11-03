@extends('layouts.app')

@section('title', 'Quản lý database')
@section('page-title', 'Quản lý database')
@section('page-description', 'Quản lý database')

@push('scripts')
    <script>
        // Global variables for JavaScript
        window.csrfToken = '{{ csrf_token() }}';
        window.yearRoutes = {
            switch: '{{ route("year.switch") }}',
            reset: '{{ route("year.reset") }}',
            export: '{{ route("year.export") }}',
            import: '{{ route("year.import") }}',
            exportDownload: '/year/export/:id/download',
            exportDelete: '/year/export/:id'
        };
        window.yearExports = @json($exports);
        window.selectedYear = '{{ $selectedYear }}';
    </script>
    <script src="{{ asset('js/year-database.js') }}"></script>
@endpush

@section('content')
    <div class="p-4 fade-in">
        <!-- Header -->
        <div class="mb-4 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold text-gray-800 mb-1">
                    <i class="fas fa-database mr-1"></i>Quản Lý Database
                </h2>
                <p class="text-xs text-gray-600">Quản lý database</p>
            </div>

            <!-- Dropdown chọn năm -->
            <div class="flex items-center space-x-2">
                <label class="text-xs font-semibold text-gray-700">
                    <i class="fas fa-calendar-alt mr-1"></i>Chọn năm xem:
                </label>
                <select id="year-selector"
                    class="px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-semibold shadow-sm"
                    onchange="switchYearFromDropdown(this.value, '{{ $selectedYear }}')">
                    @foreach($allYears as $year)
                        <option value="{{ $year->year }}" {{ $selectedYear == $year->year ? 'selected' : '' }}
                            @if(!$year->is_on_server) disabled @endif>
                            Năm {{ $year->year }}
                            @if($year->is_active) (Hiện tại) @endif
                            @if(!$year->is_on_server) (Offline - Cần import) @endif
                        </option>
                    @endforeach
                </select>

                @if($isViewingArchive)
                    <button onclick="resetToCurrentYear()"
                        class="inline-flex items-center px-3 py-1.5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors text-xs font-semibold shadow-md"
                        title="Quay lại xem dữ liệu năm hiện tại">
                        <i class="fas fa-undo mr-1"></i> Quay lại năm hiện tại
                    </button>
                @endif
            </div>
        </div>

        <!-- Banner cảnh báo khi xem dữ liệu cũ -->
        @if($isViewingArchive)
            <div
                class="mb-4 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg shadow-lg p-3 border-l-4 border-orange-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <h4 class="text-sm font-bold mb-0.5">
                            <i class="fas fa-eye mr-1"></i>Đang Xem Dữ Liệu Năm Cũ ({{ $selectedYear }})
                        </h4>
                        <p class="text-xs opacity-90">
                            Bạn đang xem dữ liệu lưu trữ. Tất cả chức năng chỉnh sửa/xóa sẽ bị vô hiệu hóa.
                            Click "Quay lại năm hiện tại" để làm việc với dữ liệu mới nhất.
                        </p>
                    </div>
                    <div class="flex-shrink-0 ml-3">
                        <button onclick="resetToCurrentYear()"
                            class="px-3 py-1.5 text-xs bg-white text-orange-600 rounded-lg hover:bg-gray-100 transition-colors font-semibold shadow">
                            <i class="fas fa-undo mr-1"></i>Quay lại
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Thông tin năm hiện tại -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-1">
                    <h5 class="text-gray-700 text-xs font-semibold">
                        <i class="fas fa-calendar-check mr-1"></i>Năm Hiện Tại
                    </h5>
                </div>
                <h2 class="text-2xl font-bold text-blue-600 mb-1">{{ $currentYear->year ?? 'N/A' }}</h2>
                <p class="text-xs text-gray-600">Database: <code
                        class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">{{ $currentYear->database_name ?? 'N/A' }}</code></p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-cyan-500">
                <div class="flex items-center justify-between mb-1">
                    <h5 class="text-gray-700 text-xs font-semibold">
                        <i class="fas fa-eye mr-1"></i>Đang Xem
                    </h5>
                </div>
                <h2 class="text-2xl font-bold text-cyan-600 mb-1">{{ $selectedYear }}</h2>
                @if($isViewingArchive)
                    <span class="inline-block bg-orange-100 text-orange-700 text-xs px-2 py-0.5 rounded-full font-semibold">Đang
                        xem dữ liệu cũ</span>
                @else
                    <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-semibold">Dữ liệu
                        hiện tại</span>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500">
                <div class="flex items-center justify-between mb-1">
                    <h5 class="text-gray-700 text-xs font-semibold">
                        <i class="fas fa-server mr-1"></i>Trên Server
                    </h5>
                </div>
                <h2 class="text-2xl font-bold text-green-600 mb-1">{{ $availableYears->count() }}</h2>
                <p class="text-xs text-gray-600">database có sẵn</p>
            </div>
        </div>

        <!-- Danh sách năm -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
            <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-4 py-3">
                <h5 class="text-white font-semibold text-sm">
                    <i class="fas fa-list mr-1"></i>Danh Sách Database
                </h5>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Năm
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Database</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng
                                Thái</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số
                                File Export</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày
                                Archive</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao
                                Tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($allYears as $year)
                            <tr class="{{ $year->is_active ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <strong class="text-gray-900 text-xs">{{ $year->year }}</strong>
                                        @if($year->is_active)
                                            <span
                                                class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Hiện tại
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">{{ $year->database_name }}</code>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if($year->is_on_server)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Trên Server
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <i class="fas fa-archive mr-1"></i> Offline
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if(isset($exports[$year->year]) && $exports[$year->year]->count() > 0)
                                        <span
                                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">
                                            {{ $exports[$year->year]->count() }} file
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">Chưa export</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                                    @if($year->archived_at)
                                        {{ $year->archived_at->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs font-medium">
                                    <div class="flex space-x-1.5">
                                        @if($year->is_on_server)
                                            @if($selectedYear != $year->year)
                                                <button
                                                    class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-cyan-600 hover:bg-cyan-700 focus:outline-none transition-colors switch-year"
                                                    data-year="{{ $year->year }}" title="Chuyển sang xem dữ liệu năm {{ $year->year }}">
                                                    <i class="fas fa-eye mr-1"></i> Xem
                                                </button>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-cyan-100 text-cyan-800">
                                                    <i class="fas fa-check mr-1"></i> Đang xem
                                                </span>
                                            @endif

                                            @hasPermission('year_database', 'can_export')
                                            <button
                                                class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none transition-colors export-db"
                                                data-year="{{ $year->year }}"
                                                title="Export database năm {{ $year->year }} ra file SQL">
                                                <i class="fas fa-download mr-1"></i> Export
                                            </button>
                                            @endhasPermission

                                            <button
                                                class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition-colors view-exports"
                                                data-year="{{ $year->year }}" title="Xem danh sách file export đã tạo">
                                                <i class="fas fa-list mr-1"></i> Files
                                            </button>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-500"
                                                title="Database offline, cần import để xem">
                                                <i class="fas fa-ban mr-1"></i> Offline
                                            </span>
                                        @endif

                                        @hasPermission('year_database', 'can_import')
                                        <button
                                            class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none transition-colors import-db"
                                            data-year="{{ $year->year }}" title="Import database từ file SQL">
                                            <i class="fas fa-upload mr-1"></i> Import
                                        </button>
                                        @endhasPermission
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center text-xs text-gray-500">Chưa có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Hướng dẫn -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-3">
                <h5 class="text-white font-semibold text-sm">
                    <i class="fas fa-info-circle mr-1"></i>Hướng Dẫn Sử Dụng
                </h5>
            </div>
            <div class="p-4 space-y-3">
                <div>
                    <h6 class="font-semibold text-gray-800 text-xs mb-1">1. Export Database:</h6>
                    <p class="text-gray-600 text-xs">Click nút "Export" → Nhập mô tả → File sẽ được tạo và lưu vào hệ thống
                    </p>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 text-xs mb-1">2. Xem File Export:</h6>
                    <p class="text-gray-600 text-xs">Click nút "Files" → Xem danh sách → Click "Tải" để download</p>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 text-xs mb-1">3. Import Database:</h6>
                    <p class="text-gray-600 text-xs">Click nút "Import" → Chọn file SQL → Xác nhận import</p>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 text-xs mb-1">4. Xem Dữ Liệu Năm Cũ:</h6>
                    <p class="text-gray-600 text-xs">Click nút "Xem" → Dữ liệu chỉ đọc, không thể sửa/xóa</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Export Database -->
    <div id="exportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-4 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-gray-900">Export Database</h3>
                <button onclick="closeModal('exportModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="exportForm">
                <input type="hidden" id="export_year" name="year">
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Năm</label>
                    <input type="text" id="export_year_display"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md bg-gray-50" readonly>
                </div>
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Mô tả (tùy chọn)</label>
                    <textarea name="description" rows="2"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="VD: Backup cuối năm 2024"></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('exportModal')"
                        class="px-3 py-1.5 text-xs bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Hủy</button>
                    <button type="button" id="btnExport"
                        class="px-3 py-1.5 text-xs bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-download mr-1"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Database -->
    <div id="importModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-4 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-gray-900">Import Database</h3>
                <button onclick="closeModal('importModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <input type="hidden" id="import_year" name="year">
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Năm</label>
                    <input type="text" id="import_year_display"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md bg-gray-50" readonly>
                </div>
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">File SQL</label>
                    <input type="file" name="file" id="import_file" accept=".sql,.gz" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Chấp nhận file .sql hoặc .sql.gz (Max 500MB)</p>
                    <div id="file_info" class="mt-2 hidden p-2 bg-blue-50 border border-blue-200 rounded text-xs">
                        <div class="flex items-center text-blue-800">
                            <i class="fas fa-file-alt mr-1"></i>
                            <span id="file_name"></span>
                        </div>
                        <div class="text-blue-600 text-xs mt-1">
                            Kích thước: <span id="file_size"></span>
                        </div>
                    </div>
                </div>
                <div class="mb-3 p-2 bg-yellow-50 border border-yellow-200 rounded-md">
                    <p class="text-xs text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Cảnh báo:</strong> Import sẽ ghi đè database hiện tại!
                    </p>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('importModal')"
                        class="px-3 py-1.5 text-xs bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Hủy</button>
                    <button type="button" id="btnImport"
                        class="px-3 py-1.5 text-xs bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                        <i class="fas fa-upload mr-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Danh Sách Export Files -->
    <div id="exportsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-4 border w-4/5 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-gray-900">Danh Sách File Export - Năm <span id="exports_year"></span>
                </h3>
                <button onclick="closeModal('exportsModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tên File</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ngày Export</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="exports_list" class="bg-white divide-y divide-gray-200">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>


@endsection