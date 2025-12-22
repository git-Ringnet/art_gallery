@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')
@section('page-title', 'Hồ sơ cá nhân')
@section('page-description', 'Quản lý thông tin tài khoản của bạn')

@section('content')
<div class="fade-in">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Update Profile Information -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Thông tin cá nhân</h3>
            
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Họ và tên</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="button" onclick="confirmUpdateProfile()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Lưu thay đổi
                </button>
            </form>
            
            <x-confirm-modal id="confirmUpdateProfileModal" title="Xác nhận cập nhật thông tin" />
        </div>

        <!-- Update Password -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Đổi mật khẩu</h3>
            
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu hiện tại</label>
                    <input type="password" id="current_password" name="current_password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu mới</label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Xác nhận mật khẩu mới</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <button type="button" onclick="confirmUpdatePassword()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-key mr-2"></i>Cập nhật mật khẩu
                </button>
            </form>
            
            <x-confirm-modal id="confirmUpdatePasswordModal" title="Xác nhận đổi mật khẩu" />
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmUpdateProfile() {
    const form = document.querySelector('form[action="{{ route('profile.update') }}"]');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    
    const summary = `
        <div class="text-left space-y-2">
            <p><strong>Họ và tên:</strong> ${name}</p>
            <p><strong>Email:</strong> ${email}</p>
        </div>
    `;
    
    showConfirmModal('confirmUpdateProfileModal', summary, () => {
        form.submit();
    });
}

function confirmUpdatePassword() {
    const form = document.querySelector('form[action="{{ route('password.update') }}"]');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const summary = `
        <div class="text-left">
            <p>Bạn có chắc chắn muốn đổi mật khẩu?</p>
        </div>
    `;
    
    showConfirmModal('confirmUpdatePasswordModal', summary, () => {
        form.submit();
    });
}
</script>
@endpush
@endsection
