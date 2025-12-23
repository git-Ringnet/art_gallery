@extends('layouts.app')

@section('title', 'Báo cáo')
@section('page-title', 'Báo cáo')
@section('page-description', 'Chọn loại báo cáo cần xem')

@section('content')
<x-alert />

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Daily Cash Collection -->
    <a href="{{ route('reports.daily-cash-collection') }}" 
       class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-purple-500 group">
        <div class="flex items-center mb-4">
            <div class="bg-purple-100 rounded-full p-3 group-hover:bg-purple-200 transition">
                <i class="fas fa-cash-register text-2xl text-purple-600"></i>
            </div>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Thu tiền mặt</h3>
        <p class="text-sm text-gray-600">Daily Cash Collection Report - Báo cáo thu tiền theo ngày/tháng, phân loại tiền mặt và thẻ</p>
    </a>

    <!-- Monthly Sales Report -->
    <a href="{{ route('reports.monthly-sales') }}" 
       class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-blue-500 group">
        <div class="flex items-center mb-4">
            <div class="bg-blue-100 rounded-full p-3 group-hover:bg-blue-200 transition">
                <i class="fas fa-chart-line text-2xl text-blue-600"></i>
            </div>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Thống kê bán hàng</h3>
        <p class="text-sm text-gray-600">Monthly Sales Report - Báo cáo doanh thu bán hàng theo tháng</p>
    </a>

    <!-- Debt Report -->
    <a href="{{ route('reports.debt-report') }}" 
       class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-red-500 group">
        <div class="flex items-center mb-4">
            <div class="bg-red-100 rounded-full p-3 group-hover:bg-red-200 transition">
                <i class="fas fa-file-invoice-dollar text-2xl text-red-600"></i>
            </div>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Công nợ</h3>
        <p class="text-sm text-gray-600">Debt Report - Báo cáo công nợ theo tháng hoặc lũy kế từ đầu năm</p>
    </a>

    <!-- Stock Import Report -->
    <a href="{{ route('reports.stock-import') }}" 
       class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-green-500 group">
        <div class="flex items-center mb-4">
            <div class="bg-green-100 rounded-full p-3 group-hover:bg-green-200 transition">
                <i class="fas fa-boxes text-2xl text-green-600"></i>
            </div>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Nhập Stock</h3>
        <p class="text-sm text-gray-600">Stock Import Report - Báo cáo tranh nhập kho theo tháng</p>
    </a>
</div>

@endsection
