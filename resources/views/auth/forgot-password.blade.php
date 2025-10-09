<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quên mật khẩu - Hệ thống Quản lý Tranh</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-cyan-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Logo -->
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-cyan-700 rounded-xl flex items-center justify-center">
                    <i class="fas fa-palette text-white text-3xl"></i>
                </div>
            </div>

            <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">Quên mật khẩu?</h2>
            <p class="text-center text-gray-600 mb-8">Nhập email để nhận link đặt lại mật khẩu</p>

            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <!-- Email -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-700 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-cyan-800 transition-all duration-200 shadow-lg mb-4">
                    <i class="fas fa-paper-plane mr-2"></i>Gửi link đặt lại mật khẩu
                </button>

                <a href="{{ route('login') }}" class="block text-center text-sm text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại đăng nhập
                </a>
            </form>
        </div>

        <p class="text-center text-gray-600 text-sm mt-6">
            © {{ date('Y') }} Hệ thống Quản lý Tranh. All rights reserved.
        </p>
    </div>
</body>
</html>
