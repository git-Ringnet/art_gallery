@extends('layouts.app')

@section('title', 'Phòng trưng bày')
@section('page-title', 'Phòng trưng bày')
@section('page-description', 'Quản lý các phòng trưng bày')

@section('header-actions')
<a href="{{ route('showrooms.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
    <i class="fas fa-plus mr-2"></i>Thêm phòng
</a>
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    @foreach($showrooms as $showroom)
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <div class="flex items-center space-x-3 mb-3">
            <img src="{{ $showroom['logo'] }}" class="w-12 h-12 rounded-lg" alt="logo">
            <div>
                <p class="font-semibold">{{ $showroom['name'] }}</p>
                <p class="text-sm text-gray-500">{{ $showroom['code'] }}</p>
            </div>
        </div>
        <p class="text-sm text-gray-600">Địa chỉ: {{ $showroom['address'] }}</p>
        <p class="text-sm text-gray-600">Điện thoại: {{ $showroom['phone'] }}</p>
        <p class="text-sm text-gray-600">Tài khoản: {{ $showroom['bank_name'] }} {{ $showroom['bank_no'] }} - {{ $showroom['bank_holder'] }}</p>
        <div class="mt-3 flex space-x-2">
            <a href="{{ route('showrooms.edit', $showroom['id']) }}" class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700">Sửa</a>
            <form action="{{ route('showrooms.destroy', $showroom['id']) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng trưng bày này?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">Xóa</button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endsection
