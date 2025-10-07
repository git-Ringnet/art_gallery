@extends('layouts.app')

@section('title', 'Báo cáo thống kê')
@section('page-title', 'Báo cáo thống kê')
@section('page-description', 'Tổng quan hệ thống quản lý tranh')

@section('content')
<div class="fade-in">
    <!-- Time Filter -->
    <div class="flex items-center mb-4 space-x-3 no-print">
        <div class="flex items-center">
            <label for="dashboard-time-filter" class="mr-3 text-sm text-gray-600">Thời gian</label>
            <select id="dashboard-time-filter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="week" {{ $period == 'week' ? 'selected' : '' }}>Tuần</option>
                <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Tháng</option>
                <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Năm</option>
            </select>
        </div>
        <div class="flex items-center space-x-2">
            <label class="text-sm text-gray-600">Từ</label>
            <input type="date" id="dashboard-from-date" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <label class="text-sm text-gray-600">Đến</label>
            <input type="date" id="dashboard-to-date" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <button id="dashboard-clear-range" class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Xóa</button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Doanh số</p>
                    <p id="dashboard-sales" class="text-2xl font-bold text-green-600">{{ number_format($stats['sales']) }}đ</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-green-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Công nợ còn lại</p>
                    <p id="dashboard-debt" class="text-2xl font-bold text-red-600">{{ number_format($stats['debt']) }}đ</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Tồn Vật tư</p>
                    <p id="dashboard-stock-supplies" class="text-2xl font-bold text-blue-600">{{ $stats['stock_supplies'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-image text-blue-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Tồn tranh</p>
                    <p id="dashboard-stock-paintings" class="text-2xl font-bold text-purple-600">{{ $stats['stock_paintings'] }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-square text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Biểu đồ doanh thu</h3>
                <span id="dashboard-range-label" class="text-sm text-gray-500">Theo {{ $period == 'week' ? 'tuần' : ($period == 'month' ? 'tháng' : 'năm') }}</span>
            </div>
            <canvas id="revenueChart" width="400" height="200"></canvas>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <h3 class="text-lg font-semibold mb-4">Phân bố sản phẩm bán ra</h3>
            <div class="flex justify-center">
                <div style="width: 550px; height: 500px;">
                    <canvas id="productChart" width="550" height="550"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <h3 class="text-lg font-semibold mb-4">Thống kê khách hàng</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                    <div>
                        <p class="text-sm text-blue-600">Khách hàng mới</p>
                        <p class="text-xl font-bold text-blue-700">{{ $stats['customer_stats']['new_customers'] }}</p>
                    </div>
                    <i class="fas fa-user-plus text-blue-500 text-2xl"></i>
                </div>
                <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                    <div>
                        <p class="text-sm text-purple-600">Tổng giao dịch</p>
                        <p class="text-xl font-bold text-purple-700">{{ $stats['customer_stats']['total_transactions'] }}</p>
                    </div>
                    <i class="fas fa-shopping-bag text-purple-500 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <h3 class="text-lg font-semibold mb-4">Thống kê kho hàng</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                    <div>
                        <p class="text-sm text-orange-600">Nhập kho tháng này</p>
                        <p class="text-xl font-bold text-orange-700">{{ $stats['inventory_stats']['imports_this_month'] }} sản phẩm</p>
                    </div>
                    <i class="fas fa-arrow-down text-orange-500 text-2xl"></i>
                </div>
                <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                    <div>
                        <p class="text-sm text-red-600">Xuất kho tháng này</p>
                        <p class="text-xl font-bold text-red-700">{{ $stats['inventory_stats']['exports_this_month'] }} sản phẩm</p>
                    </div>
                    <i class="fas fa-arrow-up text-red-500 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
            <h3 class="text-lg font-semibold mb-4">Loại tranh bán chạy</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <img src="https://vetranhtuong.biz/wp-content/uploads/2021/05/117216290_405082790477568_5157289602379512017_n.jpg" alt="Tranh" class="w-10 h-10 rounded-lg object-cover">
                        <div>
                            <p class="font-medium">Tranh sơn dầu</p>
                            <p class="text-sm text-gray-600">Đã bán: 15 | Doanh thu: 37.5M</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <img src="https://vetranhtuong.biz/wp-content/uploads/2021/05/117216290_405082790477568_5157289602379512017_n.jpg" alt="Tranh" class="w-10 h-10 rounded-lg object-cover">
                        <div>
                            <p class="font-medium">Tranh canvas</p>
                            <p class="text-sm text-gray-600">Đã bán: 8 | Doanh thu: 14.4M</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const statsData = @json($stats);
    
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: statsData.revenue_chart.labels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: statsData.revenue_chart.data,
                borderColor: 'rgb(37, 99, 235)',
                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { 
                y: { 
                    beginAtZero: true, 
                    ticks: { 
                        callback: v => (v/1000000).toFixed(1)+'M' 
                    } 
                } 
            }
        }
    });

    // Product Distribution Chart
    const productCtx = document.getElementById('productChart');
    new Chart(productCtx, {
        type: 'doughnut',
        data: {
            labels: statsData.product_distribution.labels,
            datasets: [{
                data: statsData.product_distribution.data,
                backgroundColor: [
                    'rgba(99, 102, 241, 0.8)',
                    'rgba(124, 58, 237, 0.8)',
                    'rgba(5, 150, 105, 0.8)',
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });

    // Time filter change handler
    document.getElementById('dashboard-time-filter').addEventListener('change', function() {
        window.location.href = '{{ route("dashboard.index") }}?period=' + this.value;
    });
</script>
@endpush
