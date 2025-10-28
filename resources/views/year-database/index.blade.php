@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-database"></i> Quản Lý Database Theo Năm</h2>
            <p class="text-muted">Quản lý và chuyển đổi giữa các database năm khác nhau</p>
        </div>
    </div>

    <!-- Thông tin năm hiện tại -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-calendar-check"></i> Năm Hiện Tại</h5>
                    <h2 class="text-primary">{{ $currentYear->year ?? 'N/A' }}</h2>
                    <p class="mb-0">Database: <code>{{ $currentYear->database_name ?? 'N/A' }}</code></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-eye"></i> Đang Xem</h5>
                    <h2 class="text-info">{{ $selectedYear }}</h2>
                    @if($isViewingArchive)
                        <span class="badge bg-warning">Đang xem dữ liệu cũ</span>
                    @else
                        <span class="badge bg-success">Dữ liệu hiện tại</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-server"></i> Trên Server</h5>
                    <h2 class="text-success">{{ $availableYears->count() }}</h2>
                    <p class="mb-0">database có sẵn</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách năm -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Danh Sách Database Theo Năm</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Năm</th>
                            <th>Database</th>
                            <th>Trạng Thái</th>
                            <th>Vị Trí</th>
                            <th>Ngày Archive</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allYears as $year)
                        <tr class="{{ $year->is_active ? 'table-primary' : '' }}">
                            <td>
                                <strong>{{ $year->year }}</strong>
                                @if($year->is_active)
                                    <span class="badge bg-primary ms-2">Hiện tại</span>
                                @endif
                            </td>
                            <td><code>{{ $year->database_name }}</code></td>
                            <td>
                                @if($year->is_on_server)
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> Trên Server
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-archive"></i> Offline
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($year->backup_location)
                                    <small class="text-muted">{{ Str::limit($year->backup_location, 40) }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($year->archived_at)
                                    {{ $year->archived_at->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($year->is_on_server)
                                    @if($selectedYear != $year->year)
                                        <button class="btn btn-sm btn-info switch-year" data-year="{{ $year->year }}">
                                            <i class="fas fa-exchange-alt"></i> Xem
                                        </button>
                                    @else
                                        <span class="badge bg-info">Đang xem</span>
                                    @endif
                                    
                                    @if(!$year->is_active)
                                        <button class="btn btn-sm btn-warning mark-offline" data-year="{{ $year->year }}">
                                            <i class="fas fa-cloud-download-alt"></i> Offline
                                        </button>
                                    @endif
                                @else
                                    <button class="btn btn-sm btn-success" onclick="alert('Vui lòng import database từ file backup')">
                                        <i class="fas fa-upload"></i> Import
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Chưa có dữ liệu</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hướng dẫn -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Hướng Dẫn Sử Dụng</h5>
        </div>
        <div class="card-body">
            <h6><strong>1. Cuối năm - Export dữ liệu cũ:</strong></h6>
            <pre class="bg-light p-3"><code>php artisan year:create-archive 2024</code></pre>
            <p class="text-muted">Tạo database riêng cho năm 2024 với tất cả dữ liệu</p>

            <h6 class="mt-3"><strong>2. Dọn dẹp database chính:</strong></h6>
            <pre class="bg-light p-3"><code>php artisan year:cleanup 2024</code></pre>
            <p class="text-muted">Xóa dữ liệu năm 2024 khỏi database chính (sau khi đã export)</p>

            <h6 class="mt-3"><strong>3. Chuẩn bị năm mới:</strong></h6>
            <pre class="bg-light p-3"><code>php artisan year:prepare 2025</code></pre>
            <p class="text-muted">Chuẩn bị database cho năm 2025, chỉ giữ lại tồn kho đầu kỳ</p>

            <h6 class="mt-3"><strong>4. Backup database:</strong></h6>
            <pre class="bg-light p-3"><code>php artisan year:backup 2024</code></pre>
            <p class="text-muted">Tạo file backup SQL của database năm 2024</p>

            <h6 class="mt-3"><strong>5. Đánh dấu offline (khi chuyển ra khỏi server):</strong></h6>
            <pre class="bg-light p-3"><code>php artisan year:mark-offline 2024 --location="NAS/Backup"</code></pre>
            <p class="text-muted">Đánh dấu database đã chuyển ra khỏi server</p>

            <h6 class="mt-3"><strong>6. Import lại database cũ:</strong></h6>
            <pre class="bg-light p-3"><code>php artisan year:import /path/to/backup.sql 2024</code></pre>
            <p class="text-muted">Import database từ file backup khi cần xem lại</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Chuyển năm
    $('.switch-year').click(function() {
        const year = $(this).data('year');
        
        if (confirm(`Bạn có muốn chuyển sang xem dữ liệu năm ${year}?`)) {
            $.ajax({
                url: '{{ route("year.switch") }}',
                method: 'POST',
                data: {
                    year: year,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1000);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Có lỗi xảy ra';
                    toastr.error(message);
                }
            });
        }
    });

    // Đánh dấu offline
    $('.mark-offline').click(function() {
        const year = $(this).data('year');
        const location = prompt('Nhập vị trí lưu trữ (VD: NAS, External HDD, Cloud):');
        
        if (location) {
            if (confirm(`Xác nhận đánh dấu database năm ${year} là offline?\nBạn sẽ không thể xem dữ liệu cho đến khi import lại.`)) {
                // Gọi API hoặc chạy command
                alert('Vui lòng chạy lệnh:\nphp artisan year:mark-offline ' + year + ' --location="' + location + '"');
            }
        }
    });
});
</script>
@endpush
@endsection
