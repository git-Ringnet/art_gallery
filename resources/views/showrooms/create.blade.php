@extends('layouts.app')

@section('title', 'Tạo phòng trưng bày')
@section('page-title', 'Tạo phòng trưng bày')
@section('page-description', 'Thêm phòng trưng bày mới')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
    <form action="{{ route('showrooms.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                <input type="file" name="logo" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mã phòng <span class="text-red-500">*</span></label>
                <input type="text" name="code" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="VD: SR01">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tên phòng <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Tên phòng trưng bày">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại <span class="text-red-500">*</span></label>
                <input type="text" name="phone" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="VD: 0123 456 789">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Địa chỉ <span class="text-red-500">*</span></label>
                <input type="text" name="address" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Địa chỉ">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ngân hàng</label>
                <input type="text" name="bank_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Vietcombank, ACB...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Số tài khoản</label>
                <input type="text" name="bank_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0123456789">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Chủ tài khoản</label>
                <input type="text" name="bank_holder" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nguyễn Văn A">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú</label>
                <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ghi chú..."></textarea>
            </div>
        </div>
        <div class="mt-4 flex justify-end space-x-3">
            <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">
                <i class="fas fa-save mr-2"></i>Lưu
            </button>
            <a href="{{ route('showrooms.index') }}" class="px-4 py-2 rounded bg-gray-600 text-white hover:bg-gray-700">
                <i class="fas fa-times mr-2"></i>Hủy
            </a>
        </div>
    </form>
</div>
@endsection
