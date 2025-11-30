@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title')
    Dashboard
    <button onclick="toggleHelpModal()" class="ml-2 text-blue-500 hover:text-blue-700 transition-colors" title="Hướng dẫn">
        <i class="fas fa-question-circle text-xl"></i>
    </button>
@endsection
@section('page-description', 'Tổng quan hệ thống quản lý tranh')

@section('content')
<div class="fade-in">
    <!-- Time Filter -->
    <div class="bg-white rounded-xl shadow-lg p-3 mb-4 no-print">
        <div class="flex flex-col lg:flex-row flex-wrap items-start lg:items-center gap-3">
            <div class="flex items-center w-full lg:w-auto">
                <label for="dashboard-time-filter" class="mr-2 text-xs font-medium text-gray-700 whitespace-nowrap">
                    <i class="fas fa-calendar-alt mr-1 text-blue-500"></i>Thời gian
                </label>
                <select id="dashboard-time-filter" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white flex-1 lg:flex-none" {{ !$canFilterDate ? 'disabled' : '' }}>
                    <option value="">-- Chọn --</option>
                    <option value="week" {{ $period == 'week' && !request('from_date') ? 'selected' : '' }}>Tuần này</option>
                    <option value="month" {{ $period == 'month' && !request('from_date') ? 'selected' : '' }}>Tháng này</option>
                    <option value="year" {{ $period == 'year' && !request('from_date') ? 'selected' : '' }}>Năm nay</option>
                </select>
            </div>
            
            @if($canFilterDate)
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full lg:w-auto lg:border-l lg:pl-3">
                <label class="text-xs font-medium text-gray-700 whitespace-nowrap">Hoặc chọn khoảng:</label>
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                    <input type="date" id="dashboard-from-date" 
                        value="{{ request('from_date') }}"
                        class="px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent flex-1 sm:flex-none"
                        placeholder="Từ ngày">
                    <span class="text-gray-500 text-sm">→</span>
                    <input type="date" id="dashboard-to-date" 
                        value="{{ request('to_date') }}"
                        class="px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent flex-1 sm:flex-none"
                        placeholder="Đến ngày">
                    <button id="dashboard-apply-range" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm whitespace-nowrap">
                        <i class="fas fa-filter mr-1"></i>Áp dụng
                    </button>
                    <button id="dashboard-clear-range" class="px-3 py-1.5 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm whitespace-nowrap">
                        <i class="fas fa-times mr-1"></i>Xóa
                    </button>
                </div>
            </div>
            @else
            <div class="flex items-center gap-2 px-3 py-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                <i class="fas fa-lock text-yellow-600"></i>
                <span class="text-xs text-yellow-700">Bạn không có quyền lọc theo ngày</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-gray-600 text-xs flex flex-wrap items-center gap-1">
                        <span>Doanh số</span>
                        <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded-full whitespace-nowrap">Theo bộ lọc</span>
                    </p>
                    <div id="dashboard-sales">
                        @if(($stats['sales_usd'] ?? 0) > 0)
                            <p class="text-lg font-bold text-green-600">${{ number_format($stats['sales_usd'], 2) }}</p>
                        @endif
                        @if(($stats['sales_vnd'] ?? 0) > 0)
                            <p class="text-lg font-bold text-green-600">{{ number_format($stats['sales_vnd'], 0, ',', '.') }}đ</p>
                        @endif
                        @if(($stats['sales_usd'] ?? 0) == 0 && ($stats['sales_vnd'] ?? 0) == 0)
                            <p class="text-lg font-bold text-gray-400">0đ</p>
                        @endif
                    </div>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 ml-2">
                    <i class="fas fa-chart-line text-green-600 text-lg"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-gray-600 text-xs flex flex-wrap items-center gap-1">
                        <span>Công nợ còn lại</span>
                        <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded-full whitespace-nowrap">Theo bộ lọc</span>
                    </p>
                    <div id="dashboard-debt">
                        @if(($stats['debt_usd'] ?? 0) > 0)
                            <p class="text-lg font-bold text-red-600">${{ number_format($stats['debt_usd'], 2) }}</p>
                        @endif
                        @if(($stats['debt_vnd'] ?? 0) > 0)
                            <p class="text-lg font-bold text-red-600">{{ number_format($stats['debt_vnd'], 0, ',', '.') }}đ</p>
                        @endif
                        @if(($stats['debt_usd'] ?? 0) == 0 && ($stats['debt_vnd'] ?? 0) == 0)
                            <p class="text-lg font-bold text-gray-400">0đ</p>
                        @endif
                    </div>
                </div>
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0 ml-2">
                    <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-gray-600 text-xs flex flex-wrap items-center gap-1">
                        <span>Tồn Vật tư</span>
                        <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded-full whitespace-nowrap">Theo bộ lọc</span>
                    </p>
                    <p id="dashboard-stock-supplies" class="text-xl font-bold text-blue-600">{{ $stats['stock_supplies'] }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0 ml-2">
                    <i class="fas fa-box text-blue-600 text-lg"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-gray-600 text-xs flex flex-wrap items-center gap-1">
                        <span>Tồn tranh</span>
                        <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded-full whitespace-nowrap">Theo bộ lọc</span>
                    </p>
                    <p id="dashboard-stock-paintings" class="text-xl font-bold text-purple-600">{{ $stats['stock_paintings'] }}</p>
                </div>
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 ml-2">
                    <i class="fas fa-image text-purple-600 text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-xl shadow-lg p-4">
            <div class="mb-3">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-2 gap-2">
                    <h3 class="text-base font-semibold flex flex-wrap items-center gap-1">
                        <span>Biểu đồ doanh thu</span>
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-normal whitespace-nowrap">Theo bộ lọc</span>
                    </h3>
                    <span id="dashboard-range-label" class="text-xs px-2 py-1 bg-blue-50 text-blue-700 rounded-full font-medium whitespace-nowrap">
                        Theo {{ $period == 'week' ? 'tuần' : ($period == 'month' ? 'tháng' : 'năm') }}
                    </span>
                </div>
                <p class="text-xs text-gray-500">Doanh thu thay đổi theo khoảng thời gian đã chọn</p>
            </div>
            <canvas id="revenueChart" width="400" height="200"></canvas>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-4">
            <div class="mb-3">
                <h3 class="text-base font-semibold mb-2 flex flex-wrap items-center gap-1">
                    <span>Phân bố sản phẩm bán ra</span>
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-normal whitespace-nowrap">Theo bộ lọc</span>
                </h3>
                <p class="text-xs text-gray-500">Tỷ lệ sản phẩm bán ra trong khoảng thời gian đã chọn</p>
            </div>
            <div class="flex justify-center">
                <div class="w-full max-w-md">
                    <canvas id="productChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-base font-semibold mb-2 flex flex-wrap items-center justify-between gap-2">
                <span>Thống kê khách hàng</span>
                <span class="text-xs font-normal px-2 py-0.5 bg-green-100 text-green-700 rounded-full whitespace-nowrap">Theo bộ lọc</span>
            </h3>
            <p class="text-xs text-gray-500 mb-3">Dữ liệu thay đổi theo khoảng thời gian đã chọn</p>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-2 bg-blue-50 rounded-lg">
                    <div>
                        <p class="text-xs text-blue-600">Khách hàng mới</p>
                        <p class="text-lg font-bold text-blue-700" data-stat="new-customers">{{ $stats['customer_stats']['new_customers'] }}</p>
                    </div>
                    <i class="fas fa-user-plus text-blue-500 text-xl"></i>
                </div>
                <div class="flex justify-between items-center p-2 bg-purple-50 rounded-lg">
                    <div>
                        <p class="text-xs text-purple-600">Tổng giao dịch</p>
                        <p class="text-lg font-bold text-purple-700" data-stat="total-transactions">{{ $stats['customer_stats']['total_transactions'] }}</p>
                    </div>
                    <i class="fas fa-shopping-bag text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-base font-semibold mb-2 flex flex-wrap items-center justify-between gap-2">
                <span>Thống kê kho hàng</span>
                <span class="text-xs font-normal px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full whitespace-nowrap">Tháng {{ date('m/Y') }}</span>
            </h3>
            <p class="text-xs text-gray-500 mb-3">Dữ liệu cố định theo tháng hiện tại</p>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-2 bg-orange-50 rounded-lg">
                    <div>
                        <p class="text-xs text-orange-600">Nhập kho</p>
                        <p class="text-lg font-bold text-orange-700" data-stat="imports-month">{{ $stats['inventory_stats']['imports_this_month'] }} SP</p>
                    </div>
                    <i class="fas fa-arrow-down text-orange-500 text-xl"></i>
                </div>
                <div class="flex justify-between items-center p-2 bg-red-50 rounded-lg">
                    <div>
                        <p class="text-xs text-red-600">Xuất kho</p>
                        <p class="text-lg font-bold text-red-700" data-stat="exports-month">{{ $stats['inventory_stats']['exports_this_month'] }} SP</p>
                    </div>
                    <i class="fas fa-arrow-up text-red-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-base font-semibold mb-2 flex flex-wrap items-center justify-between gap-2">
                <span>Sản phẩm đã bán</span>
                <span class="text-xs font-normal px-2 py-0.5 bg-green-100 text-green-700 rounded-full whitespace-nowrap">Theo bộ lọc</span>
            </h3>
            <p class="text-xs text-gray-500 mb-3">Top sản phẩm trong khoảng thời gian đã chọn</p>
            <div class="space-y-2" id="top-products-list">
                @forelse($stats['top_products'] as $product)
                <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                    <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm truncate">{{ $product['name'] }}</p>
                        <p class="text-xs text-gray-600">
                            Bán: {{ $product['quantity'] }} | DT: 
                            @if(($product['revenue_usd'] ?? 0) > 0)
                                ${{ number_format($product['revenue_usd'], 2) }}
                            @endif
                            @if(($product['revenue'] ?? 0) > 0)
                                {{ number_format($product['revenue']/1000000, 1) }}M
                            @endif
                        </p>
                    </div>
                </div>
                @empty
                <div class="text-center text-gray-500 py-3">
                    <p class="text-sm">Chưa có dữ liệu bán hàng</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const statsData = @json($stats);
    
    // Check if has USD or VND data
    const hasUsdData = statsData.revenue_chart.data_usd && statsData.revenue_chart.data_usd.some(v => v > 0);
    const hasVndData = statsData.revenue_chart.data_vnd && statsData.revenue_chart.data_vnd.some(v => v > 0);
    
    // Build datasets
    const datasets = [];
    if (hasVndData) {
        datasets.push({
            label: 'Doanh thu (VNĐ)',
            data: statsData.revenue_chart.data_vnd,
            borderColor: 'rgb(37, 99, 235)',
            backgroundColor: 'rgba(37, 99, 235, 0.12)',
            tension: 0.4,
            fill: true,
            yAxisID: 'yVnd'
        });
    }
    if (hasUsdData) {
        datasets.push({
            label: 'Doanh thu (USD)',
            data: statsData.revenue_chart.data_usd,
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.12)',
            tension: 0.4,
            fill: true,
            yAxisID: 'yUsd'
        });
    }
    
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: statsData.revenue_chart.labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: { 
                legend: { 
                    display: hasUsdData && hasVndData,
                    position: 'top'
                } 
            },
            scales: { 
                yVnd: { 
                    type: 'linear',
                    display: hasVndData,
                    position: 'left',
                    beginAtZero: true, 
                    ticks: { 
                        callback: v => (v/1000000).toFixed(1)+'M',
                        color: 'rgb(37, 99, 235)'
                    },
                    grid: {
                        drawOnChartArea: true
                    }
                },
                yUsd: { 
                    type: 'linear',
                    display: hasUsdData,
                    position: 'right',
                    beginAtZero: true, 
                    ticks: { 
                        callback: v => '$' + v.toLocaleString(),
                        color: 'rgb(16, 185, 129)'
                    },
                    grid: {
                        drawOnChartArea: false
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
        if (this.value) {
            window.location.href = '{{ route("dashboard.index") }}?period=' + this.value;
        }
    });

    // Apply custom date range
    document.getElementById('dashboard-apply-range').addEventListener('click', function() {
        const fromDate = document.getElementById('dashboard-from-date').value;
        const toDate = document.getElementById('dashboard-to-date').value;
        
        if (!fromDate || !toDate) {
            alert('Vui lòng chọn cả ngày bắt đầu và ngày kết thúc');
            return;
        }
        
        if (fromDate > toDate) {
            alert('Ngày bắt đầu phải nhỏ hơn ngày kết thúc');
            return;
        }
        
        window.location.href = '{{ route("dashboard.index") }}?from_date=' + fromDate + '&to_date=' + toDate;
    });

    // Clear filters
    document.getElementById('dashboard-clear-range').addEventListener('click', function() {
        window.location.href = '{{ route("dashboard.index") }}';
    });

    // Toggle help modal
    function toggleHelpModal() {
        const modal = document.getElementById('helpModal');
        modal.classList.toggle('hidden');
        if (!modal.classList.contains('hidden')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
        }
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('helpModal');
        if (event.target === modal) {
            toggleHelpModal();
        }
    });
</script>
@endpush

<!-- Help Modal -->
<div id="helpModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-6 rounded-t-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-question-circle text-3xl mr-3"></i>
                    <h3 class="text-2xl font-bold">Hướng dẫn sử dụng Dashboard</h3>
                </div>
                <button onclick="toggleHelpModal()" class="text-white hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
            <!-- Section 1 -->
            <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-filter text-blue-500 mr-2"></i>
                    Bộ lọc thời gian
                </h4>
                <p class="text-gray-600 mb-2">Sử dụng bộ lọc để xem dữ liệu theo khoảng thời gian mong muốn:</p>
                <ul class="list-disc list-inside text-gray-600 space-y-1 ml-4">
                    <li>Chọn <strong>Tuần/Tháng/Năm</strong> để xem nhanh</li>
                    <li>Hoặc chọn <strong>khoảng thời gian tùy chỉnh</strong> (Từ - Đến)</li>
                    <li>Click <strong>Áp dụng</strong> để xem kết quả</li>
                </ul>
            </div>

            <!-- Section 2 -->
            <div class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-chart-bar text-green-500 mr-2"></i>
                    Các loại chỉ số
                </h4>
                
                <!-- Dynamic Data -->
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg mb-4">
                    <div class="flex items-start">
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full font-medium mr-3 mt-0.5">
                            Theo bộ lọc
                        </span>
                        <div>
                            <p class="font-semibold text-gray-800 mb-1">Dữ liệu động</p>
                            <p class="text-sm text-gray-600">Các chỉ số này <strong>thay đổi</strong> theo khoảng thời gian bạn chọn:</p>
                            <ul class="list-disc list-inside text-sm text-gray-600 mt-2 ml-2">
                                <li>Doanh số</li>
                                <li>Biểu đồ doanh thu</li>
                                <li>Phân bố sản phẩm bán ra</li>
                                <li>Thống kê khách hàng (mới, giao dịch)</li>
                                <li>Sản phẩm bán chạy</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Static Data -->
                <div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded-lg">
                    <div class="flex items-start">
                        <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full font-medium mr-3 mt-0.5">
                            Tổng/Hiện tại
                        </span>
                        <div>
                            <p class="font-semibold text-gray-800 mb-1">Dữ liệu cố định</p>
                            <p class="text-sm text-gray-600">Các chỉ số này <strong>không bị ảnh hưởng</strong> bởi bộ lọc:</p>
                            <ul class="list-disc list-inside text-sm text-gray-600 mt-2 ml-2">
                                <li>Thống kê kho hàng (tháng hiện tại)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3 -->
            <div class="border-t pt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    Mẹo sử dụng
                </h4>
                <ul class="list-disc list-inside text-gray-600 space-y-2 ml-4">
                    <li>Sử dụng bộ lọc để so sánh doanh thu giữa các khoảng thời gian</li>
                    <li>Xem biểu đồ để nắm bắt xu hướng kinh doanh</li>
                    <li>Theo dõi công nợ và tồn kho để quản lý tốt hơn</li>
                    
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 p-4 rounded-b-xl flex justify-end">
            <button onclick="toggleHelpModal()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-check mr-2"></i>Đã hiểu
            </button>
        </div>
    </div>
</div>
