@extends('layouts.app')

@section('title', 'Chỉnh sửa nhân viên')
@section('page-title', 'Chỉnh sửa nhân viên')
@section('page-description', 'Cập nhật thông tin nhân viên')

@section('content')
    <div class="bg-white rounded-xl shadow-lg p-6 glass-effect">
        <form action="{{ route('employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tên nhân viên -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tên nhân viên <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $employee->name) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email', $employee->email) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Mật khẩu mới -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Mật khẩu mới <span class="text-gray-500 text-xs">(Để trống nếu không đổi)</span>
                    </label>
                    <input type="password" name="password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Xác nhận mật khẩu -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Xác nhận mật khẩu mới</label>
                    <input type="password" name="password_confirmation"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Ảnh đại diện mới -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ảnh đại diện mới</label>
                    <input type="file" name="avatar" accept="image/*" onchange="previewImage(event)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('avatar') border-red-500 @enderror">
                    @error('avatar')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <div id="imagePreview" class="mt-2 hidden">
                        <img id="preview" src="" alt="Preview" class="w-32 h-32 object-cover rounded-lg">
                    </div>
                </div>

                <!-- Số điện thoại -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số điện thoại</label>
                    <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Ảnh đại diện hiện tại -->
                @if($employee->avatar)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ảnh đại diện hiện tại</label>
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('storage/' . $employee->avatar) }}" alt="{{ $employee->name }}" 
                             class="w-24 h-24 object-cover rounded-lg">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remove_avatar" value="1"
                                class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500">
                            <span class="ml-2 text-sm text-red-600">Xóa ảnh</span>
                        </label>
                    </div>
                </div>
                @endif

                <!-- Trạng thái -->
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $employee->is_active) ? 'checked' : '' }}
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">Kích hoạt tài khoản</span>
                    </label>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('employees.index') }}"
                    class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Cập nhật
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
function previewImage(event) {
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    const file = event.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
}
</script>
@endpush
