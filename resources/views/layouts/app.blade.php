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
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .content-transition {
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-text {
            transition: opacity 0.2s ease-in-out;
        }

        #sidebar.collapsed .sidebar-text {
            opacity: 0;
        }

        #sidebar:not(.collapsed) .sidebar-text {
            opacity: 1;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .print-area {
                width: 100%;
            }

            #main-content {
                margin-left: 0 !important;
            }
        }

        /* Fix cho fullpage screenshot */
        body {
            position: relative;
        }

        #sidebar {
            position: fixed;
            height: 100vh;
            min-height: 100%;
            z-index: 50;
        }

        #sidebar.collapsed {
            width: 70px;
            overflow: hidden;
        }

        #sidebar.collapsed .sidebar-text {
            display: none;
            opacity: 0;
        }

        #sidebar.collapsed .sidebar-logo-text {
            display: none;
            opacity: 0;
        }

        #sidebar.collapsed .sidebar-logo-box {
            display: none;
            opacity: 0;
        }

        #sidebar.collapsed .sidebar-header {
            justify-content: center;
        }

        #sidebar.collapsed .nav-item {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }

        #sidebar.collapsed .toggle-btn-icon {
            transform: rotate(0deg);
        }

        #sidebar:not(.collapsed) .toggle-btn-icon {
            transform: rotate(0deg);
        }

        /* Dropdown styles */
        #settings-dropdown.show {
            display: block;
        }

        #settings-chevron.rotate {
            transform: rotate(180deg);
        }

        /* Smooth icon centering */
        .nav-item i {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #main-content {
            min-height: 100vh;
        }

        #main-content.sidebar-collapsed {
            margin-left: 70px !important;
        }

        /* Overlay for mobile */
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

        /* Responsive styles */
        @media (max-width: 1024px) {
            #sidebar {
                transform: translateX(-100%);
            }

            #sidebar.open {
                transform: translateX(0);
            }

            #main-content {
                margin-left: 0 !important;
            }
        }

        /* Desktop toggle button */
        @media (min-width: 1025px) {
            .mobile-toggle {
                display: none;
            }
        }
    </style>
    @stack('styles')

    <!-- Inline script to prevent sidebar flash - runs BEFORE page renders -->
    <script>
        (function () {
            // Check localStorage immediately
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed && window.innerWidth >= 1025) {
                // Add temporary style to prevent flash - will be removed after DOMContentLoaded
                const style = document.createElement('style');
                style.id = 'sidebar-preload-style';
                style.innerHTML = `
                    #sidebar { width: 70px; overflow: hidden; }
                    #main-content { margin-left: 70px; }
                    #sidebar .sidebar-text,
                    #sidebar .sidebar-logo-text,
                    #sidebar .sidebar-logo-box { display: none; opacity: 0; }
                    #sidebar .sidebar-header { justify-content: center; }
                    #sidebar .nav-item { justify-content: center; padding-left: 0; padding-right: 0; }
                `;
                document.head.appendChild(style);
            }
        })();
    </script>
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
            auth()->user()->canAccess('frames') ||
            auth()->user()->canAccess('showrooms') ||
            auth()->user()->canAccess('customers') ||
            auth()->user()->canAccess('employees') ||
            auth()->user()->canAccess('permissions') ||
            auth()->user()->canAccess('reports') ||
            auth()->user()->canAccess('year_database')
        );
    @endphp

    @if($hasAnyAccess)
        <!-- Overlay for mobile -->
        <div id="overlay" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <div id="sidebar"
            class="fixed left-0 top-0 h-full w-64 bg-gradient-to-b from-blue-600 to-cyan-700 text-white sidebar-transition shadow-2xl no-print">
            <div class="p-4">
                <div class="sidebar-header flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div
                            class="sidebar-logo-box w-10 h-10 bg-white rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-palette text-blue-600 text-xl"></i>
                        </div>
                        <h1 class="text-lg font-bold sidebar-text sidebar-logo-text">Quản lý Tranh</h1>
                    </div>
                    <button onclick="toggleSidebarCollapse()"
                        class="hidden lg:block p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors">
                        <i class="fas fa-bars text-white toggle-btn-icon"></i>
                    </button>
                </div>

                <nav class="space-y-1">
                    @canAccess('dashboard')
                    <a href="{{ route('dashboard.index') }}"
                        class="nav-item flex items-center space-x-3 p-2.5 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('dashboard.*') ? 'bg-white bg-opacity-20' : '' }}"
                        title="Dashboard">
                        <i class="fas fa-tachometer-alt w-5 flex-shrink-0"></i>
                        <span class="sidebar-text text-sm">Dashboard</span>
                    </a>
                    @endcanAccess

                    <!-- Dropdown Thiết lập -->
                    @php
                        $hasSettingsAccess = auth()->check() && auth()->user()->role && (
                            auth()->user()->canAccess('inventory') ||
                            auth()->user()->canAccess('frames') ||
                            auth()->user()->canAccess('showrooms') ||
                            auth()->user()->canAccess('customers') ||
                            auth()->user()->canAccess('employees')
                        );
                        $isSettingsActive = request()->routeIs('inventory.*') ||
                            request()->routeIs('frames.*') ||
                            request()->routeIs('showrooms.*') ||
                            request()->routeIs('customers.*') ||
                            request()->routeIs('employees.*');
                    @endphp

                    @if($hasSettingsAccess)
                        <div class="relative">
                            <button onclick="toggleSettingsDropdown()"
                                class="nav-item w-full flex items-center justify-between p-2.5 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ $isSettingsActive ? 'bg-white bg-opacity-20' : '' }}"
                                title="Thiết lập">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-cog w-5 flex-shrink-0"></i>
                                    <span class="sidebar-text text-sm">Thiết lập</span>
                                </div>
                                <i class="fas fa-chevron-down sidebar-text text-xs transition-transform duration-200"
                                    id="settings-chevron"></i>
                            </button>

                            <div id="settings-dropdown" class="mt-1 ml-2 space-y-1">
                                @canAccess('inventory')
                                <a href="{{ route('inventory.index') }}"
                                    class="nav-item flex items-center space-x-3 p-2 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('inventory.*') && !request()->routeIs('frames.*') ? 'bg-white bg-opacity-20' : '' }}"
                                    title="Quản lý kho">
                                    <i class="fas fa-warehouse w-4 flex-shrink-0 text-sm"></i>
                                    <span class="sidebar-text text-xs">Quản lý kho</span>
                                </a>
                                @endcanAccess

                                @canAccess('frames')
                                <a href="{{ route('frames.index') }}"
                                    class="nav-item flex items-center space-x-3 p-2 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('frames.*') ? 'bg-white bg-opacity-20' : '' }}"
                                    title="Khung tranh">
                                    <i class="fas fa-border-style w-4 flex-shrink-0 text-sm"></i>
                                    <span class="sidebar-text text-xs">Khung tranh</span>
                                </a>
                                @endcanAccess

                                @canAccess('showrooms')
                                <a href="{{ route('showrooms.index') }}"
                                    class="nav-item flex items-center space-x-3 p-2 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('showrooms.*') ? 'bg-white bg-opacity-20' : '' }}"
                                    title="Showroom">
                                    <i class="fas fa-store w-4 flex-shrink-0 text-sm"></i>
                                    <span class="sidebar-text text-xs">Showroom</span>
                                </a>
                                @endcanAccess

                                @canAccess('customers')
                                <a href="{{ route('customers.index') }}"
                                    class="nav-item flex items-center space-x-3 p-2 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('customers.*') ? 'bg-white bg-opacity-20' : '' }}"
                                    title="Khách hàng">
                                    <i class="fas fa-users w-4 flex-shrink-0 text-sm"></i>
                                    <span class="sidebar-text text-xs">Khách hàng</span>
                                </a>
                                @endcanAccess

                                @canAccess('employees')
                                <a href="{{ route('employees.index') }}"
                                    class="nav-item flex items-center space-x-3 p-2 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('employees.*') ? 'bg-white bg-opacity-20' : '' }}"
                                    title="Nhân viên">
                                    <i class="fas fa-user-tie w-4 flex-shrink-0 text-sm"></i>
                                    <span class="sidebar-text text-xs">Nhân viên</span>
                                </a>
                                @endcanAccess
                            </div>
                        </div>
                    @endif

                    @canAccess('sales')
                    <a href="{{ route('sales.index') }}"
                        class="nav-item flex items-center space-x-3 p-2.5 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('sales.*') ? 'bg-white bg-opacity-20' : '' }}"
                        title="Bán hàng">
                        <i class="fas fa-shopping-cart w-5 flex-shrink-0"></i>
                        <span class="sidebar-text text-sm">Bán hàng</span>
                    </a>
                    @endcanAccess

                    @canAccess('debt')
                    <a href="{{ route('debt.index') }}"
                        class="nav-item flex items-center space-x-3 p-2.5 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('debt.*') ? 'bg-white bg-opacity-20' : '' }}"
                        title="Lịch sử công nợ">
                        <i class="fas fa-credit-card w-5 flex-shrink-0"></i>
                        <span class="sidebar-text text-sm">Lịch sử công nợ</span>
                    </a>
                    @endcanAccess

                    @canAccess('returns')
                    <a href="{{ route('returns.index') }}"
                        class="nav-item flex items-center space-x-3 p-2.5 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('returns.*') ? 'bg-white bg-opacity-20' : '' }}"
                        title="Đổi/Trả hàng">
                        <i class="fas fa-undo w-5 flex-shrink-0"></i>
                        <span class="sidebar-text text-sm">Đổi/Trả hàng</span>
                    </a>
                    @endcanAccess

                    @canAccess('reports')
                    <a href="{{ route('reports.daily-cash-collection') }}"
                        class="nav-item flex items-center space-x-3 p-2.5 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('reports.*') ? 'bg-white bg-opacity-20' : '' }}"
                        title="Báo cáo">
                        <i class="fas fa-file-alt w-5 flex-shrink-0"></i>
                        <span class="sidebar-text text-sm">Báo cáo</span>
                    </a>
                    @endcanAccess

                    @canAccess('permissions')
                    <a href="{{ route('permissions.index') }}"
                        class="nav-item flex items-center space-x-3 p-2.5 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('permissions.*') ? 'bg-white bg-opacity-20' : '' }}"
                        title="Phân quyền">
                        <i class="fas fa-user-shield w-5 flex-shrink-0"></i>
                        <span class="sidebar-text text-sm">Phân quyền</span>
                    </a>
                    @endcanAccess

                    @canAccess('year_database')
                    <a href="{{ route('year.index') }}"
                        class="nav-item flex items-center space-x-3 p-2.5 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all duration-200 {{ request()->routeIs('year.*') ? 'bg-white bg-opacity-20' : '' }}"
                        title="Database">
                        <i class="fas fa-database w-5 flex-shrink-0"></i>
                        <span class="sidebar-text text-sm">Database</span>
                    </a>
                    @endcanAccess
                </nav>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <div id="main-content" class="content-transition {{ $hasAnyAccess ? 'ml-64' : '' }}">
        <!-- Admin Header (Logo + User Info) -->
        <div class="bg-white shadow-md p-3 mb-0 relative z-40 no-print">
            <div class="flex justify-between items-center">
                <!-- Menu Toggle Button (Mobile) + Logo -->
                <div class="flex items-center space-x-3">
                    @if($hasAnyAccess)
                        <button id="menu-toggle" onclick="toggleSidebar()"
                            class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-bars text-gray-700 text-xl"></i>
                        </button>
                    @endif
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-palette text-white text-lg"></i>
                        </div>
                        <h1 class="text-base font-bold text-gray-800 hidden sm:block">Quản lý Tranh</h1>
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
                        <div class="hidden sm:block">
                            <span
                                class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full whitespace-nowrap">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                <span class="hidden md:inline">Đang xem năm {{ $selectedYear }} (Chỉ đọc)</span>
                                <span class="md:hidden">Năm {{ $selectedYear }}</span>
                            </span>
                        </div>
                    @endif

                    <!-- User Profile Dropdown -->
                    <div class="relative z-50">
                        <button onclick="toggleUserDropdown()"
                            class="flex items-center space-x-2 bg-white border border-gray-300 rounded-lg px-2 py-1.5 hover:bg-gray-50 transition-colors">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=4F46E5&color=fff"
                                alt="User" class="w-7 h-7 rounded-full">
                            <span
                                class="text-sm font-medium text-gray-700 hidden sm:inline max-w-[120px] truncate">{{ Auth::user()->name }}</span>
                            <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="user-dropdown"
                            class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-[9999]">
                            <div class="py-1">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                                </div>

                                <a href="{{ route('profile.edit') }}"
                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-user mr-3 text-gray-400"></i>
                                    Hồ sơ cá nhân
                                </a>

                                <div class="border-t border-gray-100">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full text-left flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
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
        <div class="bg-white rounded-xl shadow-lg p-4 m-4 mb-4 relative z-30 no-print">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                    <p class="text-sm text-gray-600">@yield('page-description', 'Tổng quan hệ thống quản lý tranh')</p>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    @yield('header-actions')
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="px-4 pb-4">
            @if(!$hasAnyAccess)
                <!-- Thông báo không có quyền -->
                <div class="max-w-2xl mx-auto mt-12">
                    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                        <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-lock text-yellow-600 text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Chưa có quyền truy cập</h3>
                        <p class="text-gray-600 mb-6">Tài khoản của bạn chưa được cấp quyền truy cập vào bất kỳ chức năng
                            nào trong hệ thống.</p>
                        <p class="text-sm text-gray-500">Vui lòng liên hệ quản trị viên để được cấp quyền.</p>
                    </div>
                </div>
            @else
                @yield('content')
            @endif
        </div>
    </div>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }

        // Toggle sidebar collapse for desktop
        function toggleSidebarCollapse() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');

            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');

            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }

        // Restore sidebar state on page load - Apply classes for proper state management
        document.addEventListener('DOMContentLoaded', function () {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');

            if (isCollapsed && window.innerWidth >= 1025) {
                // Add the classes for proper state management
                sidebar.classList.add('collapsed');
                mainContent.classList.add('sidebar-collapsed');
            }

            // Remove the preload style so CSS classes can work normally
            const preloadStyle = document.getElementById('sidebar-preload-style');
            if (preloadStyle) {
                preloadStyle.remove();
            }
        });

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
        document.addEventListener('DOMContentLoaded', function () {
            loadAvailableYears();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
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
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                    'bg-blue-500'
                } text-white`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Toggle Settings Dropdown
        function toggleSettingsDropdown() {
            const dropdown = document.getElementById('settings-dropdown');
            const chevron = document.getElementById('settings-chevron');

            if (dropdown && chevron) {
                dropdown.classList.toggle('hidden');
                chevron.classList.toggle('rotate');
            }
        }

        // Auto-open settings dropdown if on a settings page
        document.addEventListener('DOMContentLoaded', function () {
            const isSettingsPage = {{ isset($isSettingsActive) && $isSettingsActive ? 'true' : 'false' }};
            if (isSettingsPage) {
                const dropdown = document.getElementById('settings-dropdown');
                const chevron = document.getElementById('settings-chevron');
                if (dropdown && chevron) {
                    dropdown.classList.remove('hidden');
                    chevron.classList.add('rotate');
                }
            }
        });

        // Handle nav item clicks
        document.addEventListener('DOMContentLoaded', function () {
            const navItems = document.querySelectorAll('#sidebar .nav-item');
            const sidebar = document.getElementById('sidebar');

            navItems.forEach(item => {
                item.addEventListener('click', function (e) {
                    // Only close sidebar on mobile (when it's in overlay mode)
                    if (window.innerWidth < 1025) {
                        toggleSidebar();
                    }
                    // On desktop: do nothing, just let the link navigate
                    // Sidebar state (collapsed or expanded) remains unchanged
                });
            });

            // Prevent any unwanted sidebar state changes during page transitions
            window.addEventListener('beforeunload', function () {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.getElementById('main-content');
                if (sidebar && mainContent) {
                    sidebar.style.transition = 'none';
                    mainContent.style.transition = 'none';
                }
            });
        });
    </script>
    @stack('scripts')
</body>

</html>