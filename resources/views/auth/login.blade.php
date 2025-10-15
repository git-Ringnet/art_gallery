<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập - Hệ thống Quản lý Tranh</title>
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

            <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">Đăng nhập</h2>
            <p class="text-center text-gray-600 mb-8">Hệ thống Quản lý Tranh & Khung</p>

            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Error Message -->
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Mật khẩu
                    </label>
                    <input id="password" type="password" name="password" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Ghi nhớ đăng nhập</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-800">
                            Quên mật khẩu?
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-700 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-cyan-800 transition-all duration-200 shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập
                </button>
            </form>

            @if (Route::has('register'))
                <p class="mt-6 text-center text-sm text-gray-600">
                    Chưa có tài khoản? 
                    <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-800 font-semibold">Đăng ký ngay</a>
                </p>
            @endif
        </div>

        <p class="text-center text-gray-600 text-sm mt-6">
            © {{ date('Y') }} Hệ thống Quản lý Tranh. All rights reserved.
        </p>
    </div>
</body>
</html>
