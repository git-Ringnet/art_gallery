@extends('layouts.app')

@section('title', 'Thêm nhân viên')
@section('page-title', 'Thêm nhân viên mới')
@section('page-description', 'Tạo tài khoản nhân viên mới')

@section('content')
    <!-- Confirm Modal -->
    <x-confirm-modal 
        id="confirm-employee-modal"
        title="Xác nhận thêm nhân viên"
        message="Bạn có chắc chắn muốn thêm nhân viên này?"
        confirmText="Xác nhận"
        cancelText="Quay lại"
        type="info"
    >
        <div id="confirm-employee-summary" class="text-sm"></div>
    </x-confirm-modal>

    <div class="bg-white rounded-xl shadow-lg p-4 glass-effect">
        <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" id="employee-form">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- Tên nhân viên -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Tên nhân viên <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Mật khẩu -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Mật khẩu <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Xác nhận mật khẩu -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Xác nhận mật khẩu <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Ảnh đại diện -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Ảnh đại diện</label>
                    <input type="file" name="avatar" accept="image/*" onchange="previewImage(event)"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('avatar') border-red-500 @enderror">
                    @error('avatar')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <div id="imagePreview" class="mt-2 hidden">
                        <img id="preview" src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg">
                    </div>
                </div>

                <!-- Số điện thoại -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Số điện thoại</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Trạng thái -->
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-xs font-medium text-gray-700">Kích hoạt tài khoản</span>
                    </label>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end gap-2 mt-3">
                <a href="{{ route('employees.index') }}"
                    class="bg-gray-500 text-white px-4 py-1.5 text-sm rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-1"></i>Hủy
                </a>
                <button type="button" onclick="confirmAddEmployee()"
                    class="bg-blue-600 text-white px-4 py-1.5 text-sm rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-1"></i>Lưu
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
function confirmAddEmployee() {
    const name = document.querySelector('input[name="name"]').value.trim();
    const email = document.querySelector('input[name="email"]').value.trim();
    const password = document.querySelector('input[name="password"]').value;
    
    if (!name) { alert('Vui lòng nhập tên nhân viên!'); return; }
    if (!email) { alert('Vui lòng nhập email!'); return; }
    if (!password) { alert('Vui lòng nhập mật khẩu!'); return; }
    
    let summaryHtml = `
        <div class="space-y-2">
            <div class="flex justify-between"><span class="text-gray-600">Tên:</span><span class="font-medium">${name}</span></div>
            <div class="flex justify-between"><span class="text-gray-600">Email:</span><span class="font-medium">${email}</span></div>
        </div>
    `;
    
    document.getElementById('confirm-employee-summary').innerHTML = summaryHtml;
    
    showConfirmModal('confirm-employee-modal', {
        title: 'Xác nhận thêm nhân viên',
        message: 'Vui lòng kiểm tra thông tin trước khi lưu:',
        onConfirm: function() {
            document.getElementById('employee-form').submit();
        }
    });
}

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
