@extends('layouts.app')

@section('title', 'Báo cáo')
@section('page-title', 'Báo cáo')
@section('page-description', 'Quản lý và xem các loại báo cáo')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Daily Cash Collection Report -->
    <a href="{{ route('reports.daily-cash-collection') }}" class="block bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300 border-l-4 border-blue-500">
        <div class="flex items-center mb-4">
            <div class="bg-blue-100 p-3 rounded-full mr-4">
                <i class="fas fa-cash-register text-blue-600 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">Báo cáo Thu tiền Cuối ngày</h3>
                <p class="text-sm text-gray-600">Daily Cash Collection</p>
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-4">
            Xem chi tiết doanh thu, thanh toán, giảm giá và tổng thu theo ngày
        </p>
        <div class="flex items-center text-blue-600 font-semibold">
            <span>Xem báo cáo</span>
            <i class="fas fa-arrow-right ml-2"></i>
        </div>
    </a>

    <!-- Placeholder for future reports -->
    <div class="block bg-gray-100 rounded-xl shadow-lg p-6 opacity-60 border-l-4 border-gray-400">
        <div class="flex items-center mb-4">
            <div class="bg-gray-200 p-3 rounded-full mr-4">
                <i class="fas fa-chart-line text-gray-500 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-600">Báo cáo Doanh thu</h3>
                <p class="text-sm text-gray-500">Revenue Report</p>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-4">
            Đang phát triển...
        </p>
        <div class="flex items-center text-gray-500 font-semibold">
            <span>Coming soon</span>
        </div>
    </div>

    <div class="block bg-gray-100 rounded-xl shadow-lg p-6 opacity-60 border-l-4 border-gray-400">
        <div class="flex items-center mb-4">
            <div class="bg-gray-200 p-3 rounded-full mr-4">
                <i class="fas fa-users text-gray-500 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-600">Báo cáo Khách hàng</h3>
                <p class="text-sm text-gray-500">Customer Report</p>
            </div>
        </div>
        <p class="text-sm text-gray-500 mb-4">
            Đang phát triển...
        </p>
        <div class="flex items-center text-gray-500 font-semibold">
            <span>Coming soon</span>
        </div>
    </div>
</div>
@endsection
