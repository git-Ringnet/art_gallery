@extends('layouts.app')

@section('title', 'Báo cáo Thu tiền Cuối ngày')
@section('page-title', 'Báo cáo Thu tiền Cuối ngày')
@section('page-description', 'Daily Cash Collection Report')

@section('content')
<x-alert />

<!-- Filter Form -->
<div class="bg-white rounded-xl shadow-md p-6 mb-6 no-print">
    <form method="GET" action="{{ route('reports.daily-cash-collection') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-calendar-day mr-2 text-blue-500"></i>Ngày báo cáo
                </label>
                <input type="date" 
                       name="date" 
                       value="{{ $reportDate->format('Y-m-d') }}" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-store mr-2 text-green-500"></i>Showroom
                </label>
                <select name="showroom_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Tất cả Showroom --</option>
                    @foreach($showrooms as $showroom)
                        <option value="{{ $showroom->id }}" {{ $showroomId == $showroom->id ? 'selected' : '' }}>
                            {{ $showroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-exchange-alt mr-2 text-purple-500"></i>Tỷ giá (VND/USD)
                </label>
                <input type="text" 
                       name="exchange_rate" 
                       value="{{ number_format($exchangeRate, 0, '', '') }}" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="25000">
            </div>
            
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200">
                    <i class="fas fa-search mr-2"></i>Xem báo cáo
                </button>
                <button type="button" onclick="window.print()" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-2.5 rounded-lg font-semibold shadow-lg transition-all duration-200">
                    <i class="fas fa-print"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 no-print">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90 mb-1">Deposit Total VND</p>
                <p class="text-2xl font-bold">{{ number_format($totalDepositTotalVnd, 0) }} đ</p>
                <p class="text-xs opacity-75 mt-1">${{ number_format($totalDepositUsd, 2) }} × {{ number_format($exchangeRate, 0) }} + {{ number_format($totalDepositVnd, 0) }}đ</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-file-invoice-dollar text-3xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90 mb-1">Collection (Cash + Card)</p>
                <p class="text-2xl font-bold">{{ number_format($cashCollectionVnd + $cardCollectionVnd, 0) }} đ</p>
                <p class="text-xs opacity-75 mt-1">Cash: {{ number_format($cashCollectionVnd, 0) }}đ | Card: {{ number_format($cardCollectionVnd, 0) }}đ</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-3">
                <i class="fas fa-cash-register text-3xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Report Table (for screen) -->
<div id="screen-view" class="bg-white rounded-xl shadow-lg overflow-hidden no-print">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-table mr-2 text-blue-500"></i>Chi tiết giao dịch
            </h3>
            <span class="text-sm text-gray-600">{{ count($reportData) }} items</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">No.</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Invoice</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">ID Code</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700">Customer</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-700" colspan="3">Deposit</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-700" colspan="3">Adjustment</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-700" colspan="2">Collection</th>
                    </tr>
                    <tr class="bg-gray-50 border-b text-xs">
                        <th></th><th></th><th></th><th></th>
                        <th class="px-2 py-1 text-right text-gray-600">USD</th>
                        <th class="px-2 py-1 text-right text-gray-600">VND</th>
                        <th class="px-2 py-1 text-right text-blue-700 bg-blue-50">Total VND</th>
                        <th class="px-2 py-1 text-right text-gray-600">USD</th>
                        <th class="px-2 py-1 text-right text-gray-600">VND</th>
                        <th class="px-2 py-1 text-right text-blue-700 bg-blue-50">Total VND</th>
                        <th class="px-2 py-1 text-right text-gray-600">USD</th>
                        <th class="px-2 py-1 text-right text-gray-600">VND</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData as $index => $item)
                    <tr class="border-b border-gray-100 hover:bg-blue-50 transition-colors">
                        <td class="px-3 py-2">{{ $index + 1 }}</td>
                        <td class="px-3 py-2 font-medium text-blue-600">{{ $item['invoice_code'] }}</td>
                        <td class="px-3 py-2 font-medium">{{ $item['id_code'] }}</td>
                        <td class="px-3 py-2">{{ $item['customer_name'] }}</td>
                        
                        <td class="px-2 py-2 text-right">{{ $item['deposit_usd'] > 0 ? '$' . number_format($item['deposit_usd'], 2) : '' }}</td>
                        <td class="px-2 py-2 text-right">{{ $item['deposit_vnd'] > 0 ? number_format($item['deposit_vnd'], 0) : '' }}</td>
                        <td class="px-2 py-2 text-right bg-blue-50 font-semibold text-blue-700">
                            @php $depTotal = ($item['deposit_usd'] * $exchangeRate) + $item['deposit_vnd']; @endphp
                            {{ $depTotal > 0 ? number_format($depTotal, 0) : '' }}
                        </td>
                        
                        <td class="px-2 py-2 text-right text-red-600">{{ $item['adjustment_usd'] != 0 ? '$' . number_format($item['adjustment_usd'], 2) : '' }}</td>
                        <td class="px-2 py-2 text-right text-red-600">{{ $item['adjustment_vnd'] != 0 ? number_format($item['adjustment_vnd'], 0) : '' }}</td>
                        <td class="px-2 py-2 text-right bg-blue-50 font-semibold text-red-700">
                            @php $adjTotal = ($item['adjustment_usd'] * $exchangeRate) + $item['adjustment_vnd']; @endphp
                            {{ $adjTotal != 0 ? number_format($adjTotal, 0) : '' }}
                        </td>
                        
                        <td class="px-2 py-2 text-right text-green-600">{{ $item['collection_usd'] > 0 ? '$' . number_format($item['collection_usd'], 2) : '' }}</td>
                        <td class="px-2 py-2 text-right text-green-600">{{ $item['collection_vnd'] > 0 ? number_format($item['collection_vnd'], 0) : '' }}</td>
                    </tr>
                    @endforeach
                    
                    <tr class="bg-gradient-to-r from-gray-100 to-gray-200 font-bold border-t-2">
                        <td colspan="4" class="px-3 py-3">GRAND TOTAL</td>
                        <td class="px-2 py-3 text-right">${{ number_format($totalDepositUsd, 2) }}</td>
                        <td class="px-2 py-3 text-right">{{ number_format($totalDepositVnd, 0) }}</td>
                        <td class="px-2 py-3 text-right bg-blue-100 text-blue-800 text-base">{{ number_format($totalDepositTotalVnd, 0) }}</td>
                        
                        <td class="px-2 py-3 text-right text-red-600">{{ $totalAdjustmentUsd != 0 ? '$' . number_format($totalAdjustmentUsd, 2) : '' }}</td>
                        <td class="px-2 py-3 text-right text-red-600">{{ $totalAdjustmentVnd != 0 ? number_format($totalAdjustmentVnd, 0) : '' }}</td>
                        <td class="px-2 py-3 text-right bg-blue-100 text-red-800 text-base">{{ $totalAdjustmentTotalVnd != 0 ? number_format($totalAdjustmentTotalVnd, 0) : '' }}</td>
                        
                        <td class="px-2 py-3 text-right text-green-600">${{ number_format($totalCollectionUsd, 2) }}</td>
                        <td class="px-2 py-3 text-right text-green-600">{{ number_format($totalCollectionVnd, 0) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print View -->
<div id="print-view" class="print-only" style="display: none;">
    <div class="text-center mb-4">
        <h1 class="text-lg font-bold">{{ $selectedShowroom ? $selectedShowroom->name : 'All Showrooms' }}</h1>
        @if($selectedShowroom)
        <p class="text-xs">{{ $selectedShowroom->address }}</p>
        <p class="text-xs">Tel: {{ $selectedShowroom->phone }}</p>
        @endif
        <h2 class="text-base font-bold mt-2">Daily Cash Collection Report - {{ $reportDate->format('d/m/Y') }}</h2>
        <p class="text-xs">Exchange Rate: 1 USD = {{ number_format($exchangeRate, 0) }} VND</p>
    </div>

    <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 9px;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="border: 1px solid #000; padding: 4px;">No.</th>
                <th style="border: 1px solid #000; padding: 4px;">Invoice</th>
                <th style="border: 1px solid #000; padding: 4px;">ID Code</th>
                <th style="border: 1px solid #000; padding: 4px;">Customer</th>
                <th style="border: 1px solid #000; padding: 4px;" colspan="3">Deposit</th>
                <th style="border: 1px solid #000; padding: 4px;" colspan="3">Adjustment</th>
                <th style="border: 1px solid #000; padding: 4px;" colspan="2">Collection</th>
            </tr>
            <tr style="background-color: #f9f9f9; font-size: 8px;">
                <th style="border: 1px solid #000; padding: 2px;"></th>
                <th style="border: 1px solid #000; padding: 2px;"></th>
                <th style="border: 1px solid #000; padding: 2px;"></th>
                <th style="border: 1px solid #000; padding: 2px;"></th>
                <th style="border: 1px solid #000; padding: 2px;">USD</th>
                <th style="border: 1px solid #000; padding: 2px;">VND</th>
                <th style="border: 1px solid #000; padding: 2px; background: #e3f2fd;">Total VND</th>
                <th style="border: 1px solid #000; padding: 2px;">USD</th>
                <th style="border: 1px solid #000; padding: 2px;">VND</th>
                <th style="border: 1px solid #000; padding: 2px; background: #e3f2fd;">Total VND</th>
                <th style="border: 1px solid #000; padding: 2px;">USD</th>
                <th style="border: 1px solid #000; padding: 2px;">VND</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $index => $item)
            <tr>
                <td style="border: 1px solid #000; padding: 3px;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000; padding: 3px;">{{ $item['invoice_code'] }}</td>
                <td style="border: 1px solid #000; padding: 3px;">{{ $item['id_code'] }}</td>
                <td style="border: 1px solid #000; padding: 3px;">{{ $item['customer_name'] }}</td>
                
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">{{ $item['deposit_usd'] > 0 ? number_format($item['deposit_usd'], 2) : '' }}</td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">{{ $item['deposit_vnd'] > 0 ? number_format($item['deposit_vnd'], 0) : '' }}</td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right; background: #e3f2fd; font-weight: bold;">
                    @php $depTotal = ($item['deposit_usd'] * $exchangeRate) + $item['deposit_vnd']; @endphp
                    {{ $depTotal > 0 ? number_format($depTotal, 0) : '' }}
                </td>
                
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">{{ $item['adjustment_usd'] != 0 ? number_format($item['adjustment_usd'], 2) : '' }}</td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">{{ $item['adjustment_vnd'] != 0 ? number_format($item['adjustment_vnd'], 0) : '' }}</td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right; background: #e3f2fd; font-weight: bold;">
                    @php $adjTotal = ($item['adjustment_usd'] * $exchangeRate) + $item['adjustment_vnd']; @endphp
                    {{ $adjTotal != 0 ? number_format($adjTotal, 0) : '' }}
                </td>
                
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">{{ $item['collection_usd'] > 0 ? number_format($item['collection_usd'], 2) : '' }}</td>
                <td style="border: 1px solid #000; padding: 3px; text-align: right;">{{ $item['collection_vnd'] > 0 ? number_format($item['collection_vnd'], 0) : '' }}</td>
            </tr>
            @endforeach
            
            <tr style="background-color: #e0e0e0; font-weight: bold;">
                <td colspan="4" style="border: 1px solid #000; padding: 5px;">GRAND TOTAL</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">{{ number_format($totalDepositUsd, 2) }}</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">{{ number_format($totalDepositVnd, 0) }}</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right; background: #bbdefb; font-size: 10px;">{{ number_format($totalDepositTotalVnd, 0) }}</td>
                
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">{{ $totalAdjustmentUsd != 0 ? number_format($totalAdjustmentUsd, 2) : '' }}</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">{{ $totalAdjustmentVnd != 0 ? number_format($totalAdjustmentVnd, 0) : '' }}</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right; background: #bbdefb; font-size: 10px;">{{ $totalAdjustmentTotalVnd != 0 ? number_format($totalAdjustmentTotalVnd, 0) : '' }}</td>
                
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">{{ number_format($totalCollectionUsd, 2) }}</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: right;">{{ number_format($totalCollectionVnd, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 15px; font-size: 10px;">
        <p style="margin: 3px 0;"><strong>Collection in CASH:</strong> VND {{ number_format($cashCollectionVnd, 0) }}</p>
        <p style="margin: 3px 0;"><strong>in Credit Card:</strong> VND {{ number_format($cardCollectionVnd, 0) }}</p>
        <p style="border-top: 2px solid #000; padding-top: 6px; margin-top: 6px; font-weight: bold;">
            <strong>COLLECTION TOTAL VND:</strong> VND {{ number_format($grandTotalVnd, 0) }}
        </p>
        <p style="font-size: 8px; color: #666; margin-top: 3px; font-style: italic;">
            ({{ number_format($totalCollectionUsd, 2) }} USD × {{ number_format($exchangeRate, 0) }} + {{ number_format($totalCollectionVnd, 0) }} VND)
        </p>
    </div>
</div>

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        #screen-view { display: none !important; }
        #print-view { display: block !important; }
        @page { size: A4 landscape; margin: 0.8cm; }
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
    .print-only { display: none; }
</style>
@endpush

@endsection
