<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hệ thống Quản lý Tranh & Khung')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }
        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        @media print {
            .no-print { display: none !important; }
            .print-area { width: 100%; }
            #main-content { margin-left: 0 !important; }
        }
        
        /* Fix cho fullpage screenshot */
        body {
            position: relative;
        }
        #sidebar {
            position: fixed;
            height: 100vh;
            min-height: 100%;
        }
        #main-content {
            min-height: 100vh;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.open {
                transform: translateX(0);
            }
            #main-content {
                margin-left: 0 !important;
            }
            #overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 40;
            }
            #overlay.show {
                display: block;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gradient-to-br from-blue-50 to-cyan-100 min-h-screen">
    @php
        // Kiểm tra xem user có quyền truy cập bất kỳ module nào không
        $hasAnyAccess = auth()->check() && auth()->user()->role && (
            auth()->user()->canAccess('dashboard') ||
            auth()->user()->canAccess('sales') ||
            auth()->user()->canAccess('debt') ||
            auth()->user()->canAccess('returns') ||
            auth()->user()->canAccess('inventory') ||
            auth()->user()->canAccess('showrooms') ||
            auth()->user()->canAccess('customers') ||
            auth()->user()->canAccess('employees') ||
            auth()->user()->canAccess('permissions') ||
            auth()->user()->canAccess('year_database')
        );
    @endphp

    @if($hasAnyAccess)
    <!-- Overlay for mobile -->
    <div id="overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-gradient-to-b from-blue-600 to-cyan-700 text-white sidebar-transition z-50 shadow-2xl no-print">
        <div class="p-6">
            <div class="flex items-center space-x-3 mb-8">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                    <i class="fas fa-palette text-blue-600 text-xl"></i>
                </div>
                <h1 class="text-xl font-bold">Quản lý Tranh</h1>
            </div>
            
            <nav class="space-y-2">
                @canAccess('dashboard')
                <a href="{{ route('dashboard.index') }}" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('dashboard.*') ? 'bg-white bg-opacity-20' : '' }}">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Báo cáo thống kê</span>
                </a>
                @endcanAccess
                
                @canAccess('sales')
                <a href="{{ route('sales.index') }}" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('sales.*') ? 'bg-white bg-opacity-20' : '' }}">
                    <i class="fas fa-shopping-cart w-5"></i>
                    <span>Bán hàng</span>
                </a>
                @endcanAccess
                
                @canAccess('debt')
                <a href="{{ route('debt.index') }}" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('debt.*') ? 'bg-white bg-opacity-20' : '' }}">
                    <i class="fas fa-credit-card w-5"></i>
                    <span>Lịch sử công nợ</span>
                </a>
                @endcanAccess
                
                @canAccess('returns')
                <a href="{{ route('returns.index') }}" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('returns.*') ? 'bg-white bg-opacity-20' : '' }}">
                    <i class="fas fa-undo w-5"></i>
                    <span>Đổi/Trả hàng</span>
                </a>
                @endcanAccess
                
                @canAccess('inventory')
                <a href="{{ route('inventory.index') }}" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('inventory.*') ? 'bg-white bg-opacity-20' : '' }}">
                    <i class="fas fa-warehouse w-5"></i>
                    <span>Quản lý kho</span>
                </a>
                @endcanAccess
                
                @canAccess('showrooms')
                <a href="{{ route('showrooms.index') }}" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('showrooms.*') ? 'bg-white bg-opacity-20' : '' }}">
                    <i class="fas fa-store w-5"></i>
                    <span>Phòng trưng bày</span>
                </a>
                @endcanAccess
                
                @canAccess('customers')
                <a href="{{ route('customers.index') }}" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('customers.*') ? 'bg-white bg-opacity-20' : '' }}">
                    <i class="fas fa-users w-5"></i>
                    <span>Khách hàng</span>
                </a>
                @endcanAccess
                
                @canAccess('employees')
                <a href="{{ route('employees.index') }}" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('employees.*') ? 'bg-white bg-opacity-20' : '' }}">
                    <i class="fas fa-user-tie w-5"></i>
                    <span>Nhân viên</span>
                </a>
                @endcanAccess
                
                @canAccess('permissions')
                <a href="{{ route('permissions.index') }}" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('permissions.*') ? 'bg-white bg-opacity-20' : '' }}">
                    <i class="fas fa-user-shield w-5"></i>
                    <span>Phân quyền</span>
                </a>
                @endcanAccess
                
                @canAccess('year_database')
                <a href="{{ route('year.index') }}" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('year.*') ? 'bg-white bg-opacity-20' : '' }}">
                    <i class="fas fa-database w-5"></i>
                    <span>Database</span>
                </a>
                @endcanAccess
            </nav>
        </div>
    </div>
    @endif

    <!-- Main Content -->
    <div id="main-content" class="content-transition {{ $hasAnyAccess ? 'ml-64' : '' }}">
        <!-- Admin Header (Logo + User Info) -->
        <div class="bg-white shadow-md p-4 mb-0 relative z-40 no-print">
            <div class="flex justify-between items-center">
                <!-- Menu Toggle Button (Mobile) + Logo -->
                <div class="flex items-center space-x-3">
                    @if($hasAnyAccess)
                    <button id="menu-toggle" onclick="toggleSidebar()" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-bars text-gray-700 text-xl"></i>
                    </button>
                    @endif
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-palette text-white text-lg"></i>
                        </div>
                        <h1 class="text-lg font-bold text-gray-800 hidden md:block">Quản lý Tranh</h1>
                    </div>
                </div>
                
                <!-- Archive Warning + User Profile -->
                <div class="flex items-center space-x-3">
                    <!-- Archive Warning Badge (chỉ hiện khi xem năm cũ) -->
                    @php
                        $yearService = app(\App\Services\YearDatabaseService::class);
                        $isViewingArchive = $yearService->isViewingArchive();
                        $selectedYear = $yearService->getSelectedYear();
                    @endphp
                    
                    @if($isViewingArchive)
                    <div>
                        <span class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Đang xem năm {{ $selectedYear }} (Chỉ đọc)
                        </span>
                    </div>
                    @endif

                    <!-- User Profile Dropdown -->
                <div class="relative z-50">
                    <button onclick="toggleUserDropdown()" class="flex items-center space-x-2 bg-white border border-gray-300 rounded-lg px-3 py-2 hover:bg-gray-50 transition-colors">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=4F46E5&color=fff" alt="User" class="w-8 h-8 rounded-full">
                        <span class="text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                        <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-[9999]">
                        <div class="py-1">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                            </div>
                            
                            <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                <i class="fas fa-user mr-3 text-gray-400"></i>
                                Hồ sơ cá nhân
                            </a>
                            
                            <div class="border-t border-gray-100">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <i class="fas fa-sign-out-alt mr-3"></i>
                                        Đăng xuất
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Page Header (Title + Actions) -->
        <div class="bg-white rounded-xl shadow-lg p-6 m-6 mb-6 relative z-30 no-print">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">@yield('page-title', 'Báo cáo thống kê')</h2>
                    <p class="text-gray-600">@yield('page-description', 'Tổng quan hệ thống quản lý tranh')</p>
                </div>
                <div class="flex items-center space-x-4">
                    @yield('header-actions')
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="px-6 pb-6">
            @if(!$hasAnyAccess)
            <!-- Thông báo không có quyền -->
            <div class="max-w-2xl mx-auto mt-12">
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lock text-yellow-600 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Chưa có quyền truy cập</h3>
                    <p class="text-gray-600 mb-6">Tài khoản của bạn chưa được cấp quyền truy cập vào bất kỳ chức năng nào trong hệ thống.</p>
                    <p class="text-sm text-gray-500">Vui lòng liên hệ quản trị viên để được cấp quyền.</p>
                </div>
            </div>
            @else
            @yield('content')
            @endif
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }

        function toggleUserDropdown() {
            const dropdown = document.getElementById('user-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        // Year Dropdown Functions
        function toggleYearDropdown() {
            const dropdown = document.getElementById('year-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
                if (!dropdown.classList.contains('hidden')) {
                    loadAvailableYears();
                }
            }
        }

        async function loadAvailableYears() {
            try {
                const response = await fetch('/year/info');
                const data = await response.json();
                
                const yearList = document.getElementById('year-list');
                // Kiểm tra xem element có tồn tại không (một số trang không có year dropdown)
                if (!yearList) {
                    return;
                }
                
                yearList.innerHTML = '';
                
                data.available_years.forEach(yearDb => {
                    const item = document.createElement('button');
                    item.className = 'w-full text-left px-4 py-2 text-sm hover:bg-gray-100 transition-colors flex items-center justify-between';
                    item.onclick = () => switchYear(yearDb.year);
                    
                    const yearText = document.createElement('span');
                    yearText.textContent = `Năm ${yearDb.year}`;
                    if (yearDb.is_active) {
                        yearText.className = 'font-semibold text-blue-600';
                    }
                    
                    item.appendChild(yearText);
                    
                    if (yearDb.year == data.selected_year) {
                        const check = document.createElement('i');
                        check.className = 'fas fa-check text-blue-600';
                        item.appendChild(check);
                    }
                    
                    yearList.appendChild(item);
                });
                
                // Update display
                updateYearDisplay(data);
            } catch (error) {
                console.error('Error loading years:', error);
            }
        }

        async function switchYear(year) {
            try {
                const response = await fetch('/year/switch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ year: year })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Close dropdown
                    document.getElementById('year-dropdown').classList.add('hidden');
                    
                    // Show notification
                    showNotification(data.message, 'success');
                    
                    // Update display
                    document.getElementById('current-year-display').textContent = year;
                    
                    // Show/hide archive warning
                    const archiveWarning = document.getElementById('archive-warning');
                    if (data.is_archive) {
                        archiveWarning.classList.remove('hidden');
                        document.getElementById('archive-year-text').textContent = `Đang xem năm ${year}`;
                    } else {
                        archiveWarning.classList.add('hidden');
                    }
                    
                    // Reload page to show data from selected year
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Error switching year:', error);
                showNotification('Có lỗi khi chuyển năm', 'error');
            }
        }

        function updateYearDisplay(data) {
            const currentYearDisplay = document.getElementById('current-year-display');
            if (currentYearDisplay) {
                currentYearDisplay.textContent = data.selected_year;
            }
            
            const archiveWarning = document.getElementById('archive-warning');
            if (archiveWarning) {
                if (data.is_viewing_archive) {
                    archiveWarning.classList.remove('hidden');
                    const archiveYearText = document.getElementById('archive-year-text');
                    if (archiveYearText) {
                        archiveYearText.textContent = `Đang xem năm ${data.selected_year}`;
                    }
                } else {
                    archiveWarning.classList.add('hidden');
                }
            }
        }

        // Load year info on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadAvailableYears();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('user-dropdown');
            const dropdownButton = document.querySelector('[onclick="toggleUserDropdown()"]');
            
            if (dropdown && !dropdown.contains(event.target) && !dropdownButton.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
            
            const yearDropdown = document.getElementById('year-dropdown');
            const yearButton = document.querySelector('[onclick="toggleYearDropdown()"]');
            
            if (yearDropdown && !yearDropdown.contains(event.target) && !yearButton.contains(event.target)) {
                yearDropdown.classList.add('hidden');
            }
        });

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                'bg-blue-500'
            } text-white`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Close sidebar when clicking on nav items on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('#sidebar .nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth < 768) {
                        toggleSidebar();
                    }
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
