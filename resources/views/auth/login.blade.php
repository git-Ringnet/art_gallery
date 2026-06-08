<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập - Hệ thống Quản lý Tranh</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-cyan-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md" 
         x-data="{ tab: (new URLSearchParams(window.location.search)).get('tab') || 'login', showSuccessModal: false }"
         @registration-success.window="showSuccessModal = true; tab = 'login'">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Logo -->
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-cyan-700 rounded-xl flex items-center justify-center">
                    <i class="fas fa-palette text-white text-3xl"></i>
                </div>
            </div>

            <h2 class="text-3xl font-bold text-center text-gray-800 mb-2" x-text="tab === 'login' ? 'Đăng nhập' : 'Đăng ký'">Đăng nhập</h2>
            <p class="text-center text-gray-600 mb-8" x-text="tab === 'login' ? 'Hệ thống Quản lý Tranh & Khung' : 'Đăng ký tài khoản dùng thử'">Hệ thống Quản lý Tranh & Khung</p>

            <!-- Login Form Container -->
            <div x-show="tab === 'login'">
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
                        <a href="#" @click.prevent="tab = 'register'" class="text-blue-600 hover:text-blue-800 font-semibold">Đăng ký ngay</a>
                    </p>
                @endif
            </div>

            <!-- Register Form Container -->
            <div x-show="tab === 'register'" style="display: none;">
                <form id="register-form" class="space-y-4">
                    <!-- Name (Username) -->
                    <div>
                        <x-input-label for="username" class="block text-sm font-medium text-gray-700 mb-2" :value="__('Tên đăng nhập')" />
                        <x-text-input id="username" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent block mt-1" type="text" name="username" required
                            autocomplete="name" placeholder="Nhập tên đăng nhập" />
                    </div>

                    <!-- Email Address -->
                    <div class="mt-4">
                        <x-input-label for="register_email" class="block text-sm font-medium text-gray-700 mb-2" :value="__('Email')" />
                        <x-text-input id="register_email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent block mt-1" type="email" name="email" required
                            autocomplete="username" placeholder="Nhập địa chỉ email" />
                    </div>

                    <!-- Phone Number -->
                    <div class="mt-4">
                        <x-input-label for="phone" class="block text-sm font-medium text-gray-700 mb-2" :value="__('Số điện thoại')" />
                        <x-text-input id="phone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent block mt-1" type="tel" name="phone" required
                            autocomplete="tel" placeholder="Nhập số điện thoại" />
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button class="w-full justify-center py-3 bg-gradient-to-r from-blue-600 to-cyan-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-cyan-800 transition-all duration-200 shadow-lg uppercase text-sm">
                            Đăng ký
                        </x-primary-button>
                    </div>

                    <!-- Toggle Link -->
                    <div
                        class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700/50 text-center text-sm text-gray-600 dark:text-gray-400">
                        Đã có tài khoản?
                        <a href="#" id="toggle-to-login" @click.prevent="tab = 'login'"
                            class="font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline focus:outline-none">
                            Đăng nhập tại đây
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <p class="text-center text-gray-600 text-sm mt-6">
            © {{ date('Y') }} Hệ thống Quản lý Tranh. All rights reserved.
        </p>

        <!-- Success Modal Overlay -->
        <div x-show="showSuccessModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all duration-300"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="display: none;">
            
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all duration-300 border border-gray-100"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                
                <!-- Icon Success -->
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full mb-4">
                    <i class="fas fa-check-circle text-green-600 text-2xl animate-bounce"></i>
                </div>

                <h3 class="text-xl font-bold text-center text-gray-900 mb-2">Đăng ký thành công!</h3>
                <p class="text-sm text-center text-gray-500 mb-6">Tài khoản của bạn đã được ghi nhận. Vui lòng sử dụng thông tin dùng thử sau để trải nghiệm hệ thống:</p>

                <!-- Credentials Card -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-4 mb-6 border border-blue-100/50 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/5 rounded-full -mr-8 -mt-8"></div>
                    
                    <div class="space-y-3 relative z-10 text-sm">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-200/50">
                            <span class="font-medium text-gray-500">Tài khoản chính (Admin):</span>
                            <span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">Khuyên dùng</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Email:</span>
                            <span class="font-mono text-gray-900 font-bold">admin@demo.com</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Mật khẩu:</span>
                            <span class="font-mono text-gray-900 font-bold">123456</span>
                        </div>
                    </div>
                </div>

                <!-- Other Demo Accounts Info -->
                <div class="text-xs text-gray-500 mb-6 bg-gray-50 p-3 rounded-lg border border-gray-100 text-left">
                    <p class="font-semibold mb-1 text-gray-700">Các tài khoản demo khác (Mật khẩu: 123456):</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Kế toán: <span class="font-mono">ketoan@demo.com</span></li>
                        <li>Quản kho: <span class="font-mono">quankho@demo.com</span></li>
                        <li>Bảo hành: <span class="font-mono">baohanh@demo.com</span></li>
                    </ul>
                </div>

                <!-- Action Button -->
                <button @click="showSuccessModal = false" 
                        class="w-full bg-gradient-to-r from-blue-600 to-cyan-700 text-white py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-cyan-800 transition-all duration-200 shadow-lg flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập trải nghiệm ngay
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const CONFIG = {
                hubUrl: "https://portal.app.ringnet.vn/api/demo-register",
                siteName: "Phòng Tranh"
            };

            const registerForm = document.getElementById("register-form");
            if (!registerForm) return;

            registerForm.addEventListener("submit", async (e) => {
                // 1. Ngăn chặn hành vi gửi form tải lại trang ban đầu
                e.preventDefault();

                const usernameInput = registerForm.querySelector('input[name="username"]') || registerForm.querySelector('#username') || registerForm.querySelector('input[name="name"]');
                const emailInput = registerForm.querySelector('input[name="email"]') || registerForm.querySelector('#register_email');
                const phoneInput = registerForm.querySelector('input[name="phone"]') || registerForm.querySelector('input[name="tel"]') || registerForm.querySelector('#phone');
                const submitButton = registerForm.querySelector('button[type="submit"]') || registerForm.querySelector('input[type="submit"]');

                if (!usernameInput || !emailInput || !phoneInput) {
                    alert("Không tìm thấy các trường thông tin đăng ký (Username, Email, Phone) trong form.");
                    return;
                }

                const payload = {
                    username: usernameInput.value.trim(),
                    email: emailInput.value.trim(),
                    phone: phoneInput.value.trim(),
                    site_name: CONFIG.siteName
                };

                const originalButtonHtml = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = "Đang xử lý...";

                try {
                    // 2. Gửi thông tin đăng ký lên Hub cha để ghi nhận log quan tâm
                    const response = await fetch(CONFIG.hubUrl, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json"
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (response.ok) {
                        // 3. Tự động điền tài khoản demo vào form đăng nhập
                        const loginEmailInput = document.getElementById("email");
                        const loginPasswordInput = document.getElementById("password");
                        if (loginEmailInput) loginEmailInput.value = "admin@demo.com";
                        if (loginPasswordInput) loginPasswordInput.value = "123456";

                        // 4. Reset form đăng ký
                        registerForm.reset();

                        // 5. Dispatch custom event to Alpine to show modal
                        window.dispatchEvent(new CustomEvent('registration-success'));

                        // 6. Reset submit button state
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonHtml;
                    } else {
                        alert(`Lỗi: ${result.message || "Đăng ký thất bại"}`);
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonHtml;
                    }
                } catch (error) {
                    console.error(error);
                    alert("Không thể kết nối đến hệ thống Hub trung tâm.");
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonHtml;
                }
            });
        });
    </script>
</body>
</html>
