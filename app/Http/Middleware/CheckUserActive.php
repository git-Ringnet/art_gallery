<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra nếu user đã đăng nhập
        if ($request->user() && !$request->user()->is_active) {
            // Đăng xuất user
            auth()->logout();
            
            // Xóa session
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            // Redirect về trang login với thông báo
            return redirect()->route('login')
                ->with('error', 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.');
        }

        return $next($request);
    }
}
