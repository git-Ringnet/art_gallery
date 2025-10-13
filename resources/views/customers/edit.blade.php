@extends('layouts.app')

@section('title', 'Sửa Khách hàng')
@section('page-title', 'Sửa thông tin Khách hàng')
@section('page-description', 'Cập nhật thông tin khách hàng')

@section('content')
<x-alert />

<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <form method="POST" action="{{ route('customers.update', $customer->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <h4 class="font-semibold mb-4 text-lg">Thông tin khách hàng</h4>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">
                        Tên khách hàng <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" required
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">
                        Số điện thoại
                    </label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">
                        Email
                    </label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Address -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">
                        Địa chỉ
                    </label>
                    <input type="text" name="address" value="{{ old('address', $customer->address) }}"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('address') border-red-500 @enderror">
                    @error('address')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2 text-base">
                    Ghi chú
                </label>
                <textarea name="notes" rows="3"
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('notes') border-red-500 @enderror">{{ old('notes', $customer->notes) }}</textarea>
                @error('notes')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors text-lg font-medium">
                <i class="fas fa-save mr-2"></i>Cập nhật
            </button>
            <a href="{{ route('customers.index') }}" class="flex-1 bg-gray-600 text-white py-3 rounded-lg hover:bg-gray-700 text-center transition-colors text-lg font-medium">
                <i class="fas fa-times mr-2"></i>Hủy
            </a>
        </div>
    </form>
</div>
@endsection
