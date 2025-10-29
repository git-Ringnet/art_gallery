<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Không có quyền truy cập</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-red-50 to-orange-100 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full">
        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 text-center">
            <!-- Icon -->
            <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-lock text-red-600 text-5xl"></i>
            </div>

            <!-- Error Code -->
            <h1 class="text-6xl font-bold text-gray-800 mb-4">403</h1>
            
            <!-- Title -->
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Không có quyền truy cập</h2>
            
            <!-- Message -->
            <p class="text-gray-600 mb-8 text-lg">
                {{ $exception->getMessage() ?: 'Bạn không có quyền truy cập vào trang này.' }}
            </p>

            <!-- User Info -->
            @auth
            <div class="bg-gray-50 rounded-lg p-6 mb-8 text-left">
                <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-user-circle text-gray-600 mr-2"></i>
                    Thông tin tài khoản
                </h3>
                <div class="space-y-2 text-sm text-gray-600">
                    <p><strong>Tên:</strong> {{ Auth::user()->name }}</p>
                    <p><strong>Email:</strong> {{ Auth::user()->email }}</p>
                    @if(Auth::user()->role)
                    <p><strong>Vai trò:</strong> {{ Auth::user()->role->name }}</p>
                    @else
                    <p class="text-orange-600"><strong>Vai trò:</strong> Chưa được gán</p>
                    @endif
                </div>
            </div>
            @endauth

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @auth
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-user mr-2"></i>
                    Hồ sơ cá nhân
                </a>
                @endauth
                
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Đăng xuất
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
