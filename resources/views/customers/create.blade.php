@extends('layouts.app')

@section('title', 'Thêm Khách hàng mới')
@section('page-title', 'Thêm Khách hàng mới')
@section('page-description', 'Nhập thông tin khách hàng')

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
    <form method="POST" action="{{ route('customers.store') }}">
        @csrf

        <div class="mb-4">
            <h4 class="font-semibold mb-3 text-base">Thông tin khách hàng</h4>
            
            <div class="grid grid-cols-2 gap-3 mb-3">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Tên khách hàng <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Số điện thoại
                    </label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Email
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Address -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Địa chỉ
                    </label>
                    <input type="text" name="address" value="{{ old('address') }}"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('address') border-red-500 @enderror">
                    @error('address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-3">
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                    Ghi chú
                </label>
                <textarea name="notes" rows="2"
                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-1.5 text-sm rounded-lg hover:bg-blue-700 transition-colors font-medium">
                <i class="fas fa-save mr-1"></i>Lưu
            </button>
            <a href="{{ route('customers.index') }}" class="flex-1 bg-gray-600 text-white py-1.5 text-sm rounded-lg hover:bg-gray-700 text-center transition-colors font-medium">
                <i class="fas fa-times mr-1"></i>Hủy
            </a>
        </div>
    </form>
</div>
@endsection
