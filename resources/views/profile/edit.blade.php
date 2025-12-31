@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')
@section('page-title', 'Hồ sơ cá nhân')
@section('page-description', 'Quản lý thông tin tài khoản của bạn')

@section('content')
<div class="fade-in">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Success/Error Messages -->
        @if(session('status') === 'profile-updated')
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>Thông tin cá nhân đã được cập nhật thành công!
        </div>
        @endif
        
        @if(session('status') === 'password-updated')
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>Mật khẩu đã được đổi thành công!
        </div>
        @endif
        
        <!-- Update Profile Information -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Thông tin cá nhân</h3>
            
            <form id="profile-form" method="POST" action="{{ route('profile.update') }}">
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
            
            <form id="password-form" method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu hiện tại</label>
                    <input type="password" id="current_password" name="current_password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @if($errors->updatePassword->has('current_password')) border-red-500 @endif">
                    @if($errors->updatePassword->has('current_password'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->updatePassword->first('current_password') }}</p>
                    @endif
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu mới</label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @if($errors->updatePassword->has('password')) border-red-500 @endif">
                    @if($errors->updatePassword->has('password'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->updatePassword->first('password') }}</p>
                    @endif
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
    const form = document.getElementById('profile-form');
    if (!form) {
        console.error('Profile form not found');
        return;
    }
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
    
    showConfirmModal('confirmUpdateProfileModal', {
        message: summary,
        onConfirm: function() {
            console.log('Submitting profile form...');
            form.submit();
        }
    });
}

function confirmUpdatePassword() {
    const form = document.getElementById('password-form');
    if (!form) {
        console.error('Password form not found');
        return;
    }
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const summary = `
        <div class="text-left">
            <p>Bạn có chắc chắn muốn đổi mật khẩu?</p>
        </div>
    `;
    
    showConfirmModal('confirmUpdatePasswordModal', {
        message: summary,
        onConfirm: function() {
            console.log('Submitting password form...');
            form.submit();
        }
    });
}
</script>
@endpush
@endsection
