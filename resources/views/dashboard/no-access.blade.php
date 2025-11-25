@extends('layouts.app')

@section('title', 'Chào mừng')
@section('page-title', 'Chào mừng đến với Hệ thống')
@section('page-description', 'Hệ thống quản lý tranh và khung')

@section('content')
    <div class="max-w-6xl mx-auto">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 rounded-2xl shadow-xl p-8 md:p-12 mb-6 text-white">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="mb-6 md:mb-0">
                    <h1 class="text-3xl md:text-4xl font-bold mb-3">
                        <i class="fas fa-hand-sparkles mr-3"></i>Xin chào, {{ Auth::user()->name }}!
                    </h1>
                    <p class="text-lg opacity-90">Chào mừng bạn đến với Hệ thống Quản lý Tranh & Khung</p>
                </div>
                <div
                    class="w-32 h-32 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-palette text-6xl"></i>
                </div>
            </div>
        </div>

        <!-- Account Info Card -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- User Info -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-user-circle text-blue-600 text-xl"></i>
                    </div>
                    Thông tin tài khoản
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <i class="fas fa-user w-5 text-gray-400 mr-3"></i>
                        <div>
                            <p class="text-xs text-gray-500">Họ tên</p>
                            <p class="font-medium text-gray-800">{{ Auth::user()->name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <i class="fas fa-envelope w-5 text-gray-400 mr-3"></i>
                        <div>
                            <p class="text-xs text-gray-500">Email</p>
                            <p class="font-medium text-gray-800">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    @if(Auth::user()->role)
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-user-tag w-5 text-gray-400 mr-3"></i>
                            <div>
                                <p class="text-xs text-gray-500">Vai trò</p>
                                <p class="font-medium text-gray-800">{{ Auth::user()->role->name }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-bolt text-green-600 text-xl"></i>
                    </div>
                    Thao tác nhanh
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('profile.edit') }}"
                        class="flex items-center p-3 bg-gradient-to-r from-blue-50 to-cyan-50 hover:from-blue-100 hover:to-cyan-100 rounded-lg transition-all group">
                        <div
                            class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-edit text-white"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">Chỉnh sửa hồ sơ</p>
                            <p class="text-xs text-gray-500">Cập nhật thông tin cá nhân</p>
                        </div>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center p-3 bg-gradient-to-r from-red-50 to-orange-50 hover:from-red-100 hover:to-orange-100 rounded-lg transition-all group">
                            <div
                                class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                <i class="fas fa-sign-out-alt text-white"></i>
                            </div>
                            <div class="text-left">
                                <p class="font-medium text-gray-800">Đăng xuất</p>
                                <p class="text-xs text-gray-500">Thoát khỏi hệ thống</p>
                            </div>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Access Permissions -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-shield-alt text-purple-600 text-xl"></i>
                </div>
                Quyền truy cập của bạn
            </h3>

            @php
                $modules = [
                    'dashboard' => ['name' => 'Dashboard', 'icon' => 'fa-tachometer-alt', 'color' => 'blue'],
                    'sales' => ['name' => 'Bán hàng', 'icon' => 'fa-shopping-cart', 'color' => 'green'],
                    'debt' => ['name' => 'Lịch sử công nợ', 'icon' => 'fa-credit-card', 'color' => 'yellow'],
                    'returns' => ['name' => 'Đổi/Trả hàng', 'icon' => 'fa-undo', 'color' => 'orange'],
                    'inventory' => ['name' => 'Quản lý kho', 'icon' => 'fa-warehouse', 'color' => 'indigo'],
                    'showrooms' => ['name' => 'Phòng trưng bày', 'icon' => 'fa-store', 'color' => 'pink'],
                    'customers' => ['name' => 'Khách hàng', 'icon' => 'fa-users', 'color' => 'cyan'],
                    'employees' => ['name' => 'Nhân viên', 'icon' => 'fa-user-tie', 'color' => 'teal'],
                    'permissions' => ['name' => 'Phân quyền', 'icon' => 'fa-user-shield', 'color' => 'red'],
                    'year_database' => ['name' => 'Database', 'icon' => 'fa-database', 'color' => 'purple'],
                ];

                $hasAccess = [];
                $noAccess = [];

                foreach ($modules as $key => $module) {
                    if (Auth::user()->canAccess($key)) {
                        $hasAccess[$key] = $module;
                    } else {
                        $noAccess[$key] = $module;
                    }
                }
            @endphp

            @if(count($hasAccess) > 0)
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-green-700 mb-3 uppercase flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        Các chức năng bạn có thể truy cập ({{ count($hasAccess) }})
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($hasAccess as $key => $module)
                            @php
                                $routeMap = [
                                    'dashboard' => 'dashboard.index',
                                    'sales' => 'sales.index',
                                    'debt' => 'debt.index',
                                    'returns' => 'returns.index',
                                    'inventory' => 'inventory.index',
                                    'showrooms' => 'showrooms.index',
                                    'customers' => 'customers.index',
                                    'employees' => 'employees.index',
                                    'permissions' => 'permissions.index',
                                    'year_database' => 'year.index',
                                ];
                                $routeName = $routeMap[$key] ?? 'dashboard';
                            @endphp
                            <a href="{{ route($routeName) }}"
                                class="flex items-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg hover:shadow-md transition-all group">
                                <div
                                    class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                                    <i class="fas {{ $module['icon'] }} text-white text-lg"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800">{{ $module['name'] }}</p>
                                    <p class="text-xs text-green-600 flex items-center mt-1">
                                        <i class="fas fa-arrow-right mr-1"></i>Nhấn để truy cập
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(count($noAccess) > 0)
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 mb-3 uppercase flex items-center">
                        <i class="fas fa-lock mr-2"></i>
                        Các chức năng chưa được cấp quyền ({{ count($noAccess) }})
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($noAccess as $key => $module)
                            <div class="flex items-center p-4 bg-gray-50 border border-gray-200 rounded-lg opacity-60">
                                <div class="w-12 h-12 bg-gray-300 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas {{ $module['icon'] }} text-gray-500 text-lg"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-600">{{ $module['name'] }}</p>
                                    <p class="text-xs text-gray-400 flex items-center mt-1">
                                        <i class="fas fa-lock mr-1"></i>Không có quyền
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(count($hasAccess) === 0)
                <div class="text-center py-12">
                    <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-3xl"></i>
                    </div>
                    <h4 class="text-xl font-bold text-gray-800 mb-2">Chưa có quyền truy cập</h4>
                    <p class="text-gray-600 mb-4">Tài khoản của bạn chưa được cấp quyền truy cập vào bất kỳ chức năng nào trong
                        hệ thống.</p>
                    <p class="text-sm text-gray-500">Vui lòng liên hệ quản trị viên để được hỗ trợ.</p>
                </div>
            @endif
        </div>
    </div>
@endsection