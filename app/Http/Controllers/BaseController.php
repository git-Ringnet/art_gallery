<?php

namespace App\Http\Controllers;

use App\Services\YearDatabaseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected $yearService;
    protected $connection;

    public function __construct()
    {
        // Khởi tạo YearDatabaseService
        $this->yearService = app(YearDatabaseService::class);
        
        // Lấy connection name dựa trên năm đang xem
        $this->connection = $this->yearService->getConnection();
    }

    /**
     * Lấy connection name hiện tại
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Kiểm tra có đang xem dữ liệu năm cũ không
     */
    protected function isViewingArchive()
    {
        return $this->yearService->isViewingArchive();
    }

    /**
     * Lấy năm đang xem
     */
    protected function getSelectedYear()
    {
        return $this->yearService->getSelectedYear();
    }

    /**
     * Lấy năm hiện tại
     */
    protected function getCurrentYear()
    {
        return $this->yearService->getCurrentYear();
    }
}
