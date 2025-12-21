@extends('layouts.app')

@section('title', 'Backup & Restore Database')
@section('page-title', 'Backup & Restore Database')
@section('page-description', 'Backup & Restore Database')

@push('scripts')
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.exportRoute = '{{ route("year.export") }}';
        window.importRoute = '{{ route("year.import") }}';
        window.importPrepareRoute = '{{ route("year.import.prepare") }}';
        window.importSqlRoute = '{{ route("year.import.sql") }}';
        window.importImagesBatchRoute = '{{ route("year.import.images-batch") }}';
        window.importCleanupRoute = '{{ route("year.import.cleanup") }}';
        window.uploadImagesBatchRoute = '{{ route("year.upload-images-batch") }}';
        window.isArchiveMode = {{ app(\App\Services\YearDatabaseService::class)->isViewingArchive() ? 'true' : 'false' }};
    </script>
@endpush

@section('content')
    <div class="p-4 fade-in">
        <!-- Header -->
        <div class="mb-4">
            <h2 class="text-lg font-bold text-gray-800 mb-1">
                <i class="fas fa-database mr-1"></i>Backup & Restore Database
            </h2>
            <p class="text-xs text-gray-600">Export database để backup và Import để khôi phục dữ liệu</p>
        </div>

        <!-- Thông tin database hiện tại -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-1">
                    <h5 class="text-gray-700 text-xs font-semibold">
                        <i class="fas fa-calendar-check mr-1"></i>Database Hiện Tại
                    </h5>
                </div>
                <h2 class="text-2xl font-bold text-blue-600 mb-1">{{ $currentYear->year ?? date('Y') }}</h2>
                <p class="text-xs text-gray-600">Database: <code
                        class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">{{ $currentYear->database_name ?? env('DB_DATABASE') }}</code>
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500">
                <div class="flex items-center justify-between mb-1">
                    <h5 class="text-gray-700 text-xs font-semibold">
                        <i class="fas fa-file-archive mr-1"></i>File Backup
                    </h5>
                </div>
                <h2 class="text-2xl font-bold text-green-600 mb-1">{{ $exportsCount }}</h2>
                <p class="text-xs text-gray-600">file backup đã tạo</p>
            </div>
        </div>

        <!-- Thông báo chuyển năm tự động (hiện từ 15/12) -->
        @php
            $now = now();
            $showYearEndGuide = $now->month == 12 && $now->day >= 15;
            $newYear = $now->year + 1;
            $daysLeft = 31 - $now->day + 1;
        @endphp
        
        @if($showYearEndGuide)
        <div class="mb-4 p-4 rounded-lg shadow-md border-l-4 bg-green-50 border-green-500">
            <div class="flex items-start">
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-bold text-green-800">
                        Chuyển năm TỰ ĐỘNG: {{ $now->year }} → {{ $newYear }}
                    </h3>
                    <p class="mt-1 text-xs text-green-700">
                        Hệ thống sẽ <strong>tự động chuyển năm</strong> vào lúc <strong>00:05 ngày 1/1/{{ $newYear }}</strong>
                    </p>
                    <div class="mt-2 p-3 bg-green-100 rounded-lg text-xs text-green-800">
                        <p class="font-semibold mb-1">Lịch chạy tự động:</p>
                        <ul class="list-disc list-inside space-y-0.5 ml-2">
                            <li><strong>23:00 ngày 31/12</strong>: Backup an toàn trước khi chuyển năm</li>
                            <li><strong>00:05 ngày 1/1</strong>: Export → Cleanup → Chuẩn bị năm mới</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            <!-- Export Card -->
            @hasPermission('year_database', 'can_export')
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-center mb-3">
                        <div class="bg-green-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-download text-lg text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Export Database</h3>
                            <p class="text-xs text-gray-600">Tạo file backup của database hiện tại</p>
                        </div>
                    </div>
                    <button onclick="openExportModal()"
                        class="w-full px-3 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold">
                        <i class="fas fa-download mr-1"></i> Export Database
                    </button>
                </div>
            @endhasPermission

            <!-- Import Card -->
            @hasPermission('year_database', 'can_import')
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-center mb-3">
                        <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-upload text-lg text-yellow-600"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Import Database</h3>
                            <p class="text-xs text-gray-600">Khôi phục database từ file backup</p>
                        </div>
                    </div>
                    <button onclick="openImportModal()"
                        class="w-full px-3 py-2 text-sm bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors font-semibold">
                        <i class="fas fa-upload mr-1"></i> Import Database
                    </button>
                </div>
            @endhasPermission
        </div>

        <!-- Danh sách file backup -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-4 py-3">
                <h5 class="text-white font-semibold text-sm">
                    <i class="fas fa-history mr-1"></i>Lịch Sử Backup & Restore
                </h5>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên File</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kích Thước</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chi Tiết</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mô Tả</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($exports as $export)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    @if(($export->type ?? 'export') === 'import')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-upload mr-1"></i> Import
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-download mr-1"></i> Export
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">{{ $export->filename }}</code>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-600">
                                    @if($export->file_size > 0)
                                        {{ $export->file_size_formatted }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    <div class="flex flex-col gap-1">
                                        @if($export->includes_images)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-images mr-1"></i> Kèm hình
                                            </span>
                                        @endif
                                        @if($export->is_encrypted)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <i class="fas fa-lock mr-1"></i> Mã hóa
                                            </span>
                                        @endif
                                        @if(!$export->includes_images && !$export->is_encrypted && ($export->type ?? 'export') === 'export')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                <i class="fas fa-database mr-1"></i> SQL
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-600 max-w-xs truncate" title="{{ $export->description }}">
                                    {{ $export->description ?: '-' }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                                    {{ $export->exported_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs font-medium">
                                    <div class="flex space-x-1.5">
                                        @if($export->fileExists())
                                            <a href="{{ route('year.export.download', $export->id) }}"
                                                class="inline-flex items-center px-2 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                                <i class="fas fa-download mr-1"></i> Tải
                                            </a>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 bg-gray-300 text-gray-500 rounded-md cursor-not-allowed" title="File không tồn tại">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Mất file
                                            </span>
                                        @endif
                                        @hasPermission('year_database', 'can_delete')
                                            <button onclick="deleteExport({{ $export->id }})"
                                                class="inline-flex items-center px-2 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                                <i class="fas fa-trash mr-1"></i> Xóa
                                            </button>
                                        @endhasPermission
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2"></i>
                                    <p class="text-sm">Chưa có lịch sử backup/restore nào</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Hướng dẫn -->
        <div class="mt-4 bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-3">
                <h5 class="text-white font-semibold text-sm">
                    <i class="fas fa-info-circle mr-1"></i>Hướng Dẫn Sử Dụng
                </h5>
            </div>
            <div class="p-4 space-y-3">
                <div>
                    <h6 class="font-semibold text-gray-800 text-xs mb-1"><i class="fas fa-download text-green-600 mr-1"></i>Export Database (Backup):</h6>
                    <p class="text-gray-600 text-xs">Click "Export Database" → Nhập mô tả (tùy chọn) → File SQL được tự động mã hóa AES-256 và lưu vào lịch sử</p>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 text-xs mb-1"><i class="fas fa-file-archive text-blue-600 mr-1"></i>Export kèm hình ảnh (Khuyến nghị):</h6>
                    <p class="text-gray-600 text-xs">Tick "Kèm hình ảnh" để tạo file ZIP chứa database + toàn bộ hình ảnh. File ZIP được lưu vào lịch sử để tải lại khi cần</p>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 text-xs mb-1"><i class="fas fa-upload text-yellow-600 mr-1"></i>Import ZIP (Khuyến nghị):</h6>
                    <p class="text-gray-600 text-xs">Chọn file .zip đã export → Database và hình ảnh được khôi phục đầy đủ → File được lưu vào lịch sử</p>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 text-xs mb-1"><i class="fas fa-database text-gray-600 mr-1"></i>Import SQL + Thư mục ảnh riêng:</h6>
                    <p class="text-gray-600 text-xs">Dùng khi có file SQL và thư mục ảnh tách rời → Khôi phục nhanh nhưng <strong>không lưu vào lịch sử</strong> (vì không đầy đủ trong 1 file)</p>
                </div>
                <div class="p-2 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-xs text-blue-800">
                        <i class="fas fa-lightbulb mr-1"></i>
                        <strong>Khuyến nghị:</strong> Sử dụng Export/Import ZIP để có backup đầy đủ và dễ quản lý trong lịch sử
                    </p>
                </div>
                <div class="p-2 bg-yellow-50 border border-yellow-200 rounded-md">
                    <p class="text-xs text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Lưu ý:</strong> Import sẽ ghi đè toàn bộ dữ liệu hiện tại. Hãy export backup trước khi import!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Export -->
    <div id="exportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-4 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-gray-900">Export Database</h3>
                <button onclick="closeModal('exportModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="exportForm">
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Mô tả (tùy chọn)</label>
                    <textarea name="description" rows="2"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="VD: Backup trước khi cập nhật hệ thống"></textarea>
                </div>
                <div class="mb-3">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="include_images" id="include_images_checkbox" value="1"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-xs text-gray-700">
                            <i class="fas fa-images mr-1"></i>Kèm hình ảnh (file ZIP)
                        </span>
                    </label>
                </div>
                <div class="mb-3 p-2 bg-green-50 border border-green-200 rounded-md">
                    <p class="text-xs text-green-800">
                        <i class="fas fa-lock mr-1"></i>
                        File SQL sẽ được tự động mã hóa AES-256
                    </p>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('exportModal')"
                        class="px-3 py-1.5 text-xs bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Hủy</button>
                    <button type="button" onclick="handleExport()" id="exportBtn"
                        class="px-3 py-1.5 text-xs bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-download mr-1"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import -->
    <div id="importModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-4 border w-[450px] shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-semibold text-gray-900">Import Database</h3>
                <button onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600" id="closeImportBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Step 1: Chọn file -->
            <div id="import_step1">
                <form id="importForm" enctype="multipart/form-data">
                    <!-- Tab chọn loại import -->
                    <div class="mb-3 flex border-b">
                        <button type="button" onclick="switchImportTab('sql')" id="tab_sql"
                            class="px-3 py-2 text-xs font-medium border-b-2 border-blue-600 text-blue-600">
                            <i class="fas fa-database mr-1"></i>Import SQL
                        </button>
                        <button type="button" onclick="switchImportTab('zip')" id="tab_zip"
                            class="px-3 py-2 text-xs font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            <i class="fas fa-file-archive mr-1"></i>Import ZIP (SQL + Hình)
                        </button>
                    </div>

                    <!-- Tab SQL -->
                    <div id="import_tab_sql">
                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">File SQL</label>
                            <input type="file" name="file" id="import_file_sql" accept=".sql,.gz"
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Chấp nhận file .sql hoặc .sql.gz</p>
                        </div>
                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Thư mục hình ảnh (tùy chọn)</label>
                            <input type="file" id="import_images_folder" webkitdirectory directory multiple
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1 text-xs text-gray-500">
                                Chọn thư mục <strong>storage</strong> hoặc thư mục chứa các folder: paintings, avatars, showrooms, supplies
                            </p>
                            <div id="folder_info" class="mt-2 hidden p-2 bg-green-50 border border-green-200 rounded text-xs text-green-700">
                                <i class="fas fa-folder-open mr-1"></i>
                                <span id="folder_file_count">0</span> file hình ảnh
                            </div>
                        </div>
                    </div>

                    <!-- Tab ZIP -->
                    <div id="import_tab_zip" class="hidden">
                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">File ZIP</label>
                            <input type="file" name="file" id="import_file_zip" accept=".zip"
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1 text-xs text-gray-500">File ZIP chứa database.sql và thư mục storage/</p>
                        </div>
                        <div id="zip_file_info" class="hidden p-2 bg-blue-50 border border-blue-200 rounded text-xs mb-3">
                            <div class="flex items-center text-blue-800">
                                <i class="fas fa-file-archive mr-1"></i>
                                <span id="zip_file_name"></span>
                            </div>
                            <div class="text-blue-600 text-xs mt-1">
                                Kích thước: <span id="zip_file_size"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 p-2 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-xs text-red-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>CẢNH BÁO:</strong> Import sẽ ghi đè TOÀN BỘ dữ liệu hiện tại!
                        </p>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeImportModal()"
                            class="px-3 py-1.5 text-xs bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Hủy</button>
                        <button type="button" onclick="handleImport()" id="importBtn"
                            class="px-3 py-1.5 text-xs bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                            <i class="fas fa-upload mr-1"></i> Import
                        </button>
                    </div>
                </form>
            </div>

            <!-- Step 2: Progress -->
            <div id="import_step2" class="hidden">
                <div class="space-y-4">
                    <!-- Status -->
                    <div id="import_status" class="text-center">
                        <i class="fas fa-spinner fa-spin text-2xl text-blue-600 mb-2"></i>
                        <p class="text-sm font-medium text-gray-700" id="import_status_text">Đang xử lý...</p>
                    </div>

                    <!-- Progress bar -->
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span id="progress_label">Tiến trình</span>
                            <span id="progress_percent">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div id="progress_bar" class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1" id="progress_detail"></p>
                    </div>

                    <!-- Steps indicator -->
                    <div class="space-y-2">
                        <div class="flex items-center text-xs" id="step_upload">
                            <i class="fas fa-circle text-gray-300 mr-2" id="step_upload_icon"></i>
                            <span>Upload & giải nén file</span>
                        </div>
                        <div class="flex items-center text-xs" id="step_sql">
                            <i class="fas fa-circle text-gray-300 mr-2" id="step_sql_icon"></i>
                            <span>Import database</span>
                        </div>
                        <div class="flex items-center text-xs" id="step_images">
                            <i class="fas fa-circle text-gray-300 mr-2" id="step_images_icon"></i>
                            <span>Copy hình ảnh (<span id="images_count">0</span> file)</span>
                        </div>
                    </div>

                    <!-- Cancel button (chỉ hiện khi đang xử lý) -->
                    <div class="flex justify-center" id="import_cancel_wrapper">
                        <button type="button" onclick="cancelImport()" id="cancelImportBtn"
                            class="px-4 py-2 text-xs bg-red-600 text-white rounded-md hover:bg-red-700">
                            <i class="fas fa-stop mr-1"></i> Hủy
                        </button>
                    </div>

                    <!-- Done button (hiện khi hoàn thành) -->
                    <div class="flex justify-center hidden" id="import_done_wrapper">
                        <button type="button" onclick="finishImport()"
                            class="px-4 py-2 text-xs bg-green-600 text-white rounded-md hover:bg-green-700">
                            <i class="fas fa-check mr-1"></i> Hoàn tất
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Import state
        let importSessionId = null;
        let importCancelled = false;
        const BATCH_SIZE = 20; // Số file copy mỗi batch

        // Upload file với progress bar
        function uploadFileWithProgress(file) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                const formData = new FormData();
                formData.append('file', file);

                console.log('Starting upload:', file.name, 'Size:', formatFileSize(file.size));

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        updateProgress(percent * 0.08, `Đang upload: ${percent}%`); // 0-8%
                        updateStatus(`Đang upload file... ${percent}%`);
                        console.log('Upload progress:', percent + '%');
                    }
                });

                xhr.addEventListener('load', () => {
                    console.log('Upload complete, status:', xhr.status);
                    console.log('Response:', xhr.responseText.substring(0, 500));
                    
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            if (data.success) {
                                updateStatus('Đang giải nén...');
                                updateProgress(8, 'Upload xong, đang giải nén...');
                                console.log('Prepare success:', data);
                                resolve(data);
                            } else {
                                reject(new Error(data.message || 'Lỗi không xác định'));
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                            reject(new Error('Lỗi parse response: ' + e.message));
                        }
                    } else {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            reject(new Error(data.message || 'Upload thất bại'));
                        } catch (e) {
                            reject(new Error('Upload thất bại: HTTP ' + xhr.status + ' - ' + xhr.responseText.substring(0, 200)));
                        }
                    }
                });

                xhr.addEventListener('error', (e) => {
                    console.error('XHR error:', e);
                    reject(new Error('Lỗi kết nối mạng'));
                });

                xhr.addEventListener('timeout', () => {
                    console.error('XHR timeout');
                    reject(new Error('Upload timeout - file quá lớn'));
                });

                xhr.open('POST', window.importPrepareRoute);
                xhr.setRequestHeader('X-CSRF-TOKEN', window.csrfToken);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.timeout = 600000; // 10 phút timeout
                xhr.send(formData);
            });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function openExportModal() {
            document.getElementById('include_images_checkbox').checked = false;
            openModal('exportModal');
        }

        let currentImportTab = 'sql';
        let selectedImages = [];

        function openImportModal() {
            // Kiểm tra archive mode trước khi mở modal
            if (window.isArchiveMode) {
                alert('Không thể thực hiện thao tác này khi đang xem dữ liệu năm cũ. Vui lòng chuyển về năm hiện tại.');
                return;
            }
            
            document.getElementById('import_file_sql').value = '';
            document.getElementById('import_file_zip').value = '';
            document.getElementById('import_images_folder').value = '';
            document.getElementById('folder_info').classList.add('hidden');
            document.getElementById('zip_file_info').classList.add('hidden');
            document.getElementById('import_step1').classList.remove('hidden');
            document.getElementById('import_step2').classList.add('hidden');
            switchImportTab('sql');
            importSessionId = null;
            importCancelled = false;
            selectedImages = [];
            openModal('importModal');
        }

        function switchImportTab(tab) {
            currentImportTab = tab;
            document.getElementById('tab_sql').classList.toggle('border-blue-600', tab === 'sql');
            document.getElementById('tab_sql').classList.toggle('text-blue-600', tab === 'sql');
            document.getElementById('tab_sql').classList.toggle('border-transparent', tab !== 'sql');
            document.getElementById('tab_sql').classList.toggle('text-gray-500', tab !== 'sql');
            
            document.getElementById('tab_zip').classList.toggle('border-blue-600', tab === 'zip');
            document.getElementById('tab_zip').classList.toggle('text-blue-600', tab === 'zip');
            document.getElementById('tab_zip').classList.toggle('border-transparent', tab !== 'zip');
            document.getElementById('tab_zip').classList.toggle('text-gray-500', tab !== 'zip');
            
            document.getElementById('import_tab_sql').classList.toggle('hidden', tab !== 'sql');
            document.getElementById('import_tab_zip').classList.toggle('hidden', tab !== 'zip');
        }

        // Xử lý chọn folder hình ảnh
        document.getElementById('import_images_folder').addEventListener('change', function(e) {
            const files = Array.from(e.target.files).filter(f => 
                f.type.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(f.name)
            );
            selectedImages = files;
            
            if (files.length > 0) {
                document.getElementById('folder_file_count').textContent = files.length;
                document.getElementById('folder_info').classList.remove('hidden');
            } else {
                document.getElementById('folder_info').classList.add('hidden');
            }
        });

        // Xử lý chọn file ZIP
        document.getElementById('import_file_zip').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('zip_file_name').textContent = file.name;
                document.getElementById('zip_file_size').textContent = formatFileSize(file.size);
                document.getElementById('zip_file_info').classList.remove('hidden');
            } else {
                document.getElementById('zip_file_info').classList.add('hidden');
            }
        });

        function closeImportModal() {
            if (importSessionId && !importCancelled) {
                if (!confirm('Đang import, bạn có chắc muốn hủy?')) {
                    return;
                }
                cancelImport();
            }
            closeModal('importModal');
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        function handleExport() {
            const form = document.getElementById('exportForm');
            const formData = new FormData(form);
            formData.append('year', {{ $currentYear->year ?? date('Y') }});
            
            const includeImages = document.getElementById('include_images_checkbox').checked;
            formData.append('include_images', includeImages ? '1' : '0');

            let confirmMsg = 'Xác nhận export database hiện tại?\n\nFile sẽ được mã hóa tự động';
            if (includeImages) {
                confirmMsg += '\nKèm hình ảnh (ZIP)';
            }

            if (!confirm(confirmMsg)) {
                return;
            }

            const btn = document.getElementById('exportBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang export...';

            fetch(window.exportRoute, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.message || 'Có lỗi xảy ra'); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Export thành công!\n\nFile: ' + data.export.filename);
                        closeModal('exportModal');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Export error:', error);
                    alert(error.message || 'Có lỗi xảy ra');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-download mr-1"></i> Export';
                });
        }

        async function handleImport() {
            if (currentImportTab === 'sql') {
                // Import SQL + folder hình ảnh
                const sqlFile = document.getElementById('import_file_sql').files[0];
                if (!sqlFile) {
                    alert('Vui lòng chọn file SQL');
                    return;
                }

                let confirmMsg = `⚠️ CẢNH BÁO: Import sẽ GHI ĐÈ TOÀN BỘ dữ liệu hiện tại!\n\n` +
                    `File SQL: ${sqlFile.name}\n` +
                    `Kích thước: ${formatFileSize(sqlFile.size)}`;
                
                if (selectedImages.length > 0) {
                    confirmMsg += `\n📷 ${selectedImages.length} file hình ảnh`;
                }
                
                confirmMsg += `\n\nBạn có chắc chắn muốn tiếp tục?`;

                if (!confirm(confirmMsg)) return;

                await handleSqlAndImagesImport(sqlFile, selectedImages);
            } else {
                // Import ZIP
                const zipFile = document.getElementById('import_file_zip').files[0];
                if (!zipFile) {
                    alert('Vui lòng chọn file ZIP');
                    return;
                }

                let confirmMsg = `⚠️ CẢNH BÁO: Import sẽ GHI ĐÈ TOÀN BỘ dữ liệu hiện tại!\n\n` +
                    `File ZIP: ${zipFile.name}\n` +
                    `Kích thước: ${formatFileSize(zipFile.size)}\n` +
                    `Sẽ import database + hình ảnh theo batch\n\n` +
                    `Bạn có chắc chắn muốn tiếp tục?`;

                if (!confirm(confirmMsg)) return;

                await handleZipImport(zipFile);
            }
        }

        async function handleSqlImport(file) {
            const btn = document.getElementById('importBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang import...';

            const formData = new FormData();
            formData.append('file', file);
            formData.append('year', {{ $currentYear->year ?? date('Y') }});

            try {
                const response = await fetch(window.importRoute, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const data = await response.json();
                
                if (data.is_archive_mode) {
                    alert(data.message);
                    return;
                }
                
                if (data.success) {
                    alert(data.message);
                    closeModal('importModal');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            } catch (error) {
                console.error('Import error:', error);
                alert('Có lỗi xảy ra');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Import';
            }
        }

        async function handleSqlAndImagesImport(sqlFile, images) {
            // Chuyển sang step 2 (progress)
            document.getElementById('import_step1').classList.add('hidden');
            document.getElementById('import_step2').classList.remove('hidden');
            document.getElementById('closeImportBtn').classList.add('hidden');
            
            resetProgress();
            importCancelled = false;
            document.getElementById('images_count').textContent = images.length;

            let tempSessionId = null;

            try {
                // Step 1: Upload ảnh TRƯỚC (khi session còn valid)
                if (images.length > 0) {
                    updateStep('upload', 'processing');
                    updateStatus('Đang upload hình ảnh lên server...');
                    
                    const batchSize = 10; // Upload 10 file mỗi lần
                    let uploaded = 0;
                    
                    // Tạo session ID để lưu ảnh tạm
                    tempSessionId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                    
                    for (let i = 0; i < images.length && !importCancelled; i += batchSize) {
                        const batch = images.slice(i, i + batchSize);
                        const batchFormData = new FormData();
                        
                        batchFormData.append('session_id', tempSessionId);
                        batchFormData.append('save_to_temp', '1'); // Lưu vào thư mục tạm
                        
                        batch.forEach((img, idx) => {
                            batchFormData.append('images[]', img);
                            const relativePath = img.webkitRelativePath || img.name;
                            batchFormData.append('paths[]', relativePath);
                        });

                        try {
                            const response = await fetch(window.uploadImagesBatchRoute, {
                                method: 'POST',
                                headers: { 
                                    'X-CSRF-TOKEN': window.csrfToken,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: batchFormData
                            });
                            
                            const result = await response.json();
                            
                            if (!response.ok) {
                                throw new Error(result.message || 'Server error: ' + response.status);
                            }
                            
                            uploaded += result.uploaded || batch.length;
                        } catch (e) {
                            console.error('Upload batch error:', e);
                            // Tiếp tục với các batch khác
                        }

                        const progress = (uploaded / images.length) * 30;
                        updateProgress(progress, `Đã upload ${uploaded}/${images.length} hình ảnh`);
                    }
                    
                    updateStep('upload', 'done');
                } else {
                    updateStep('upload', 'done');
                }

                if (importCancelled) return;

                // Step 2: Import SQL
                updateStep('sql', 'processing');
                updateStatus('Đang import database...');
                updateProgress(35, 'Đang import SQL...');

                const formData = new FormData();
                formData.append('file', sqlFile);
                formData.append('year', {{ $currentYear->year ?? date('Y') }});
                if (images.length > 0) {
                    formData.append('has_images', '1');
                    formData.append('images_count', images.length);
                    formData.append('temp_session_id', tempSessionId); // Để server biết copy ảnh từ temp
                }

                const sqlResponse = await fetch(window.importRoute, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const sqlData = await sqlResponse.json();
                
                if (sqlData.is_archive_mode) {
                    throw new Error(sqlData.message);
                }
                
                if (!sqlData.success) {
                    throw new Error(sqlData.message);
                }

                updateStep('sql', 'done');
                updateProgress(70, 'Đã import database');

                // Step 3: Server đã copy ảnh từ temp vào storage (trong importDatabase)
                updateStep('images', 'done');

                // Done!
                updateProgress(100, 'Hoàn tất!');
                updateStatus('Import thành công!');
                document.getElementById('import_status').innerHTML = 
                    '<i class="fas fa-check-circle text-4xl text-green-600 mb-2"></i>' +
                    '<p class="text-sm font-medium text-green-700">Import thành công!</p>';
                
                document.getElementById('import_cancel_wrapper').classList.add('hidden');
                document.getElementById('import_done_wrapper').classList.remove('hidden');

            } catch (error) {
                console.error('Import error:', error);
                document.getElementById('import_status').innerHTML = 
                    '<i class="fas fa-times-circle text-4xl text-red-600 mb-2"></i>' +
                    '<p class="text-sm font-medium text-red-700">Lỗi: ' + error.message + '</p>';
                
                document.getElementById('import_cancel_wrapper').classList.add('hidden');
                document.getElementById('import_done_wrapper').classList.remove('hidden');
            }
        }

        async function handleZipImport(file) {
            // Chuyển sang step 2 (progress)
            document.getElementById('import_step1').classList.add('hidden');
            document.getElementById('import_step2').classList.remove('hidden');
            document.getElementById('closeImportBtn').classList.add('hidden');
            
            resetProgress();
            importCancelled = false;
            
            console.log('=== BẮT ĐẦU IMPORT ZIP ===');
            console.log('File:', file.name, 'Size:', formatFileSize(file.size));

            try {
                // Step 1: Upload & prepare với XMLHttpRequest để có progress
                updateStep('upload', 'processing');
                updateStatus('Đang upload file...');
                
                console.log('Step 1: Upload file...');
                const prepareData = await uploadFileWithProgress(file);
                console.log('Prepare response:', prepareData);
                
                if (!prepareData.success) {
                    throw new Error(prepareData.message || 'Upload thất bại');
                }

                importSessionId = prepareData.sessionId;
                const totalImages = prepareData.totalImages;
                document.getElementById('images_count').textContent = totalImages;
                
                console.log('SessionId:', importSessionId, 'Total images:', totalImages);
                
                updateStep('upload', 'done');
                updateProgress(10, 'Đã giải nén file');

                if (importCancelled) {
                    console.log('Import cancelled after upload');
                    return;
                }

                // Step 2: Import SQL
                updateStep('sql', 'processing');
                updateStatus('Đang import database...');
                
                console.log('Step 2: Import SQL...');
                const sqlResponse = await fetch(window.importSqlRoute, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ sessionId: importSessionId })
                });
                
                if (!sqlResponse.ok) {
                    const errorData = await sqlResponse.json();
                    throw new Error(errorData.message || 'Import SQL thất bại');
                }
                
                const sqlData = await sqlResponse.json();
                console.log('SQL import response:', sqlData);
                
                if (!sqlData.success) {
                    throw new Error(sqlData.message || 'Import SQL thất bại');
                }
                
                // Cập nhật CSRF token mới (vì import SQL có thể reset sessions)
                if (sqlData.newCsrfToken) {
                    window.csrfToken = sqlData.newCsrfToken;
                    console.log('Updated CSRF token');
                }

                updateStep('sql', 'done');
                updateProgress(30, 'Đã import database');

                if (importCancelled) {
                    console.log('Import cancelled after SQL');
                    return;
                }

                // Step 3: Copy images theo batch
                if (totalImages > 0) {
                    updateStep('images', 'processing');
                    updateStatus('Đang copy hình ảnh...');
                    
                    console.log('Step 3: Copy images...');
                    let processed = 0;
                    let batchCount = 0;
                    
                    while (processed < totalImages && !importCancelled) {
                        batchCount++;
                        console.log(`Batch ${batchCount}: startIndex=${processed}, batchSize=${BATCH_SIZE}`);
                        
                        const batchResponse = await fetch(window.importImagesBatchRoute, {
                            method: 'POST',
                            headers: { 
                                'X-CSRF-TOKEN': window.csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                sessionId: importSessionId,
                                startIndex: processed,
                                batchSize: BATCH_SIZE
                            })
                        });
                        
                        // Check response status
                        if (!batchResponse.ok) {
                            const errorText = await batchResponse.text();
                            console.error('Batch response error:', batchResponse.status, errorText.substring(0, 500));
                            throw new Error(`HTTP ${batchResponse.status}: ${batchResponse.statusText}`);
                        }
                        
                        const batchData = await batchResponse.json();
                        console.log(`Batch ${batchCount} response:`, batchData);
                        
                        if (!batchData.success) {
                            throw new Error(batchData.message || 'Copy images thất bại');
                        }

                        processed = batchData.processed;
                        const imageProgress = 30 + (processed / totalImages) * 70;
                        updateProgress(imageProgress, `Đã copy ${processed}/${totalImages} hình ảnh`);
                        
                        if (batchData.isComplete) {
                            console.log('All images copied');
                            break;
                        }
                    }
                    
                    updateStep('images', 'done');
                } else {
                    console.log('No images to copy');
                    updateStep('images', 'done');
                }

                // Cleanup session và ghi nhận import thành công
                console.log('Cleanup session và ghi nhận import...');
                await fetch(window.importCleanupRoute, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ 
                        sessionId: importSessionId,
                        success: true,
                        filename: file.name,
                        totalImages: totalImages
                    })
                });

                // Done!
                console.log('=== IMPORT HOÀN TẤT ===');
                updateProgress(100, 'Hoàn tất!');
                updateStatus('Import thành công!');
                document.getElementById('import_status').innerHTML = 
                    '<i class="fas fa-check-circle text-4xl text-green-600 mb-2"></i>' +
                    '<p class="text-sm font-medium text-green-700">Import thành công!</p>';
                
                document.getElementById('import_cancel_wrapper').classList.add('hidden');
                document.getElementById('import_done_wrapper').classList.remove('hidden');
                importSessionId = null;

            } catch (error) {
                console.error('=== IMPORT LỖI ===', error);
                updateStatus('Lỗi: ' + error.message);
                document.getElementById('import_status').innerHTML = 
                    '<i class="fas fa-times-circle text-4xl text-red-600 mb-2"></i>' +
                    '<p class="text-sm font-medium text-red-700">Lỗi: ' + error.message + '</p>';
                
                document.getElementById('import_cancel_wrapper').classList.add('hidden');
                document.getElementById('import_done_wrapper').classList.remove('hidden');
                
                // Cleanup on error
                if (importSessionId) {
                    console.log('Cleanup on error...');
                    try {
                        await fetch(window.importCleanupRoute, {
                            method: 'POST',
                            headers: { 
                                'X-CSRF-TOKEN': window.csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ sessionId: importSessionId })
                        });
                    } catch (e) {
                        console.error('Cleanup error:', e);
                    }
                }
            }
        }

        function resetProgress() {
            updateProgress(0, '');
            ['upload', 'sql', 'images'].forEach(step => updateStep(step, 'pending'));
        }

        function updateProgress(percent, detail) {
            document.getElementById('progress_bar').style.width = percent + '%';
            document.getElementById('progress_percent').textContent = Math.round(percent) + '%';
            document.getElementById('progress_detail').textContent = detail;
        }

        function updateStatus(text) {
            document.getElementById('import_status_text').textContent = text;
        }

        function updateStep(step, status) {
            const icon = document.getElementById(`step_${step}_icon`);
            icon.className = 'fas mr-2 ';
            
            switch (status) {
                case 'pending':
                    icon.className += 'fa-circle text-gray-300';
                    break;
                case 'processing':
                    icon.className += 'fa-spinner fa-spin text-blue-600';
                    break;
                case 'done':
                    icon.className += 'fa-check-circle text-green-600';
                    break;
                case 'error':
                    icon.className += 'fa-times-circle text-red-600';
                    break;
            }
        }

        async function cancelImport() {
            importCancelled = true;
            
            if (importSessionId) {
                await fetch(window.importCleanupRoute, {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ sessionId: importSessionId })
                });
                importSessionId = null;
            }
            
            closeModal('importModal');
        }

        function finishImport() {
            closeModal('importModal');
            location.reload();
        }

        function deleteExport(id) {
            // Kiểm tra archive mode trước
            if (window.isArchiveMode) {
                alert('Không thể thực hiện thao tác này khi đang xem dữ liệu năm cũ. Vui lòng chuyển về năm hiện tại.');
                return;
            }
            
            if (!confirm('Xác nhận xóa file backup này?')) {
                return;
            }

            fetch(`/year/export/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.is_archive_mode) {
                        alert(data.message);
                        return;
                    }
                    if (data.success) {
                        alert('Đã xóa file');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    alert('Có lỗi xảy ra');
                });
        }
    </script>
@endsection
