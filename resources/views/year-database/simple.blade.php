@extends('layouts.app')

@section('title', 'Backup & Restore Database')
@section('page-title', 'Backup & Restore Database')
@section('page-description', 'Backup & Restore Database')

@push('scripts')
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.exportRoute = '{{ route("year.export") }}';
        window.importRoute = '{{ route("year.import") }}';
    </script>
@endpush

@section('content')
    <div class="p-6 fade-in">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                <i class="fas fa-database mr-2"></i>Backup & Restore Database
            </h2>
            <p class="text-gray-600">Export database để backup và Import để khôi phục dữ liệu</p>
        </div>

        <!-- Thông tin database hiện tại -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-2">
                    <h5 class="text-gray-700 font-semibold">
                        <i class="fas fa-calendar-check mr-2"></i>Database Hiện Tại
                    </h5>
                </div>
                <h2 class="text-3xl font-bold text-blue-600 mb-2">{{ $currentYear->year ?? date('Y') }}</h2>
                <p class="text-sm text-gray-600">Database: <code
                        class="bg-gray-100 px-2 py-1 rounded">{{ $currentYear->database_name ?? env('DB_DATABASE') }}</code>
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between mb-2">
                    <h5 class="text-gray-700 font-semibold">
                        <i class="fas fa-file-archive mr-2"></i>File Backup
                    </h5>
                </div>
                <h2 class="text-3xl font-bold text-green-600 mb-2">{{ $exportsCount }}</h2>
                <p class="text-sm text-gray-600">file backup đã tạo</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- Export Card -->
            @hasPermission('year_database', 'can_export')
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-download text-2xl text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Export Database</h3>
                            <p class="text-sm text-gray-600">Tạo file backup của database hiện tại</p>
                        </div>
                    </div>
                    <button onclick="openExportModal()"
                        class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold">
                        <i class="fas fa-download mr-2"></i> Export Database
                    </button>
                </div>
            @endhasPermission

            <!-- Import Card -->
            @hasPermission('year_database', 'can_import')
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-yellow-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-upload text-2xl text-yellow-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Import Database</h3>
                            <p class="text-sm text-gray-600">Khôi phục database từ file backup</p>
                        </div>
                    </div>
                    <button onclick="openImportModal()"
                        class="w-full px-4 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors font-semibold">
                        <i class="fas fa-upload mr-2"></i> Import Database
                    </button>
                </div>
            @endhasPermission
        </div>

        <!-- Danh sách file backup -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
                <h5 class="text-white font-semibold text-lg">
                    <i class="fas fa-list mr-2"></i>Danh Sách File Backup
                </h5>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên
                                File</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kích
                                Thước</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mô Tả
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày
                                Tạo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao
                                Tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($exports as $export)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <code class="bg-gray-100 px-2 py-1 rounded text-sm">{{ $export->filename }}</code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $export->file_size_formatted }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $export->description ?: '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $export->exported_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('year.export.download', $export->id) }}"
                                            class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-download mr-1"></i> Tải
                                        </a>
                                        @hasPermission('year_database', 'can_delete')
                                            <button onclick="deleteExport({{ $export->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                                <i class="fas fa-trash mr-1"></i> Xóa
                                            </button>
                                        @endhasPermission
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>Chưa có file backup nào</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Hướng dẫn -->
        <div class="mt-6 bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-cyan-500 to-blue-600 px-6 py-4">
                <h5 class="text-white font-semibold text-lg">
                    <i class="fas fa-info-circle mr-2"></i>Hướng Dẫn Sử Dụng
                </h5>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <h6 class="font-semibold text-gray-800 mb-2">1. Export Database (Backup):</h6>
                    <p class="text-gray-600 text-sm">Click "Export Database" → Nhập mô tả (tùy chọn) → File sẽ được tạo
                        và lưu vào hệ thống</p>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 mb-2">2. Import Database (Restore):</h6>
                    <p class="text-gray-600 text-sm">Click "Import Database" → Chọn file SQL → Xác nhận → Dữ liệu hiện tại
                        sẽ bị ghi đè</p>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 mb-2">3. Tải File Backup:</h6>
                    <p class="text-gray-600 text-sm">Click nút "Tải" để download file backup về máy</p>
                </div>
                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Lưu ý:</strong> Import sẽ ghi đè toàn bộ dữ liệu hiện tại. Hãy export backup trước khi
                        import!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Export -->
    <div id="exportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Export Database</h3>
                <button onclick="closeModal('exportModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="exportForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mô tả (tùy chọn)</label>
                    <textarea name="description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        placeholder="VD: Backup trước khi cập nhật hệ thống"></textarea>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('exportModal')"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Hủy</button>
                    <button type="button" onclick="handleExport()"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-download mr-1"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import -->
    <div id="importModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Import Database</h3>
                <button onclick="closeModal('importModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">File SQL</label>
                    <input type="file" name="file" id="import_file" accept=".sql,.gz" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Chấp nhận file .sql hoặc .sql.gz (Max 500MB)</p>
                    <div id="file_info" class="mt-2 hidden p-2 bg-blue-50 border border-blue-200 rounded text-sm">
                        <div class="flex items-center text-blue-800">
                            <i class="fas fa-file-alt mr-2"></i>
                            <span id="file_name"></span>
                        </div>
                        <div class="text-blue-600 text-xs mt-1">
                            Kích thước: <span id="file_size"></span>
                        </div>
                    </div>
                </div>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm text-red-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>CẢNH BÁO:</strong> Import sẽ ghi đè TOÀN BỘ dữ liệu hiện tại!
                    </p>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('importModal')"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Hủy</button>
                    <button type="button" onclick="handleImport()"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                        <i class="fas fa-upload mr-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function openExportModal() {
            openModal('exportModal');
        }

        function openImportModal() {
            document.getElementById('import_file').value = '';
            document.getElementById('file_info').classList.add('hidden');
            openModal('importModal');
        }

        // Show file info
        document.getElementById('import_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('file_name').textContent = file.name;
                document.getElementById('file_size').textContent = formatFileSize(file.size);
                document.getElementById('file_info').classList.remove('hidden');
            } else {
                document.getElementById('file_info').classList.add('hidden');
            }
        });

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

            if (!confirm('Xác nhận export database hiện tại?')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang export...';

            fetch(window.exportRoute, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Export thành công!\n\nFile: ' + data.export.filename);
                        closeModal('exportModal');
                        location.reload();
                    } else {
                        alert('❌ Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Export error:', error);
                    alert('❌ Có lỗi xảy ra');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-download mr-1"></i> Export';
                });
        }

        function handleImport() {
            const form = document.getElementById('importForm');
            const formData = new FormData(form);
            formData.append('year', {{ $currentYear->year ?? date('Y') }});

            const fileInput = document.getElementById('import_file');
            if (!fileInput.files.length) {
                alert('Vui lòng chọn file SQL');
                return;
            }

            const file = fileInput.files[0];
            const confirmMsg = `⚠️ CẢNH BÁO: Import sẽ GHI ĐÈ TOÀN BỘ dữ liệu hiện tại!\n\n` +
                `File: ${file.name}\n` +
                `Kích thước: ${formatFileSize(file.size)}\n\n` +
                `Bạn có chắc chắn muốn tiếp tục?`;

            if (!confirm(confirmMsg)) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang import...';

            fetch(window.importRoute, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Import thành công!\n\nDatabase đã được khôi phục.');
                        closeModal('importModal');
                        location.reload();
                    } else {
                        alert('❌ Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Import error:', error);
                    alert('❌ Có lỗi xảy ra');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-upload mr-1"></i> Import';
                });
        }

        function deleteExport(id) {
            if (!confirm('Xác nhận xóa file backup này?')) {
                return;
            }

            fetch(`/year/export/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
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
