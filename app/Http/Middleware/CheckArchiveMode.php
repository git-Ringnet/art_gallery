<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\YearDatabaseService;
use Symfony\Component\HttpFoundation\Response;

class CheckArchiveMode
{
    protected $yearService;

    /**
     * Các routes được phép thực hiện POST ngay cả khi đang xem năm cũ
     * CHỈ cho phép các thao tác cần thiết
     */
    protected $excludedRoutes = [
        'year.switch',          // Chuyển năm
        'year.reset',           // Reset về năm hiện tại
        'year.export',          // Export database (chỉ đọc dữ liệu)
        'year.export.download', // Download file backup (chỉ đọc)
        'logout',               // Đăng xuất
    ];

    public function __construct(YearDatabaseService $yearService)
    {
        $this->yearService = $yearService;
    }

    /**
     * Block TẤT CẢ action thêm/sửa/xóa khi đang xem dữ liệu năm cũ
     * Năm cũ = CHỈ XEM, không được làm gì hết
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Nếu đang xem năm cũ (archive mode)
        if ($this->yearService->isViewingArchive()) {
            $currentRoute = $request->route()?->getName();
            
            // Chỉ cho phép các route cần thiết (chuyển năm, đăng xuất)
            if ($currentRoute && \in_array($currentRoute, $this->excludedRoutes, true)) {
                return $next($request);
            }

            // Chặn TẤT CẢ request không phải GET
            if (!$request->isMethod('GET')) {
                $message = 'Không thể thực hiện thao tác này khi đang xem dữ liệu năm cũ. Vui lòng chuyển về năm hiện tại.';
                
                // Nếu là AJAX request
                if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'is_archive_mode' => true,
                    ], 403);
                }

                // Nếu là request thường - redirect với error
                return redirect()->back()
                    ->with('error', $message)
                    ->withInput();
            }
        }

        return $next($request);
    }
}
