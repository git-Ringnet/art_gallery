<?php

namespace App\Services;

use App\Models\YearDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class YearDatabaseService
{
    protected $currentYear;
    protected $selectedYear;

    public function __construct()
    {
        $this->currentYear = YearDatabase::getCurrentYear();
        $this->selectedYear = session('selected_year', $this->currentYear?->year ?? date('Y'));
    }

    /**
     * Lấy năm hiện tại
     */
    public function getCurrentYear()
    {
        return $this->currentYear?->year ?? date('Y');
    }

    /**
     * Lấy năm đang được chọn
     */
    public function getSelectedYear()
    {
        return $this->selectedYear;
    }

    /**
     * Set năm được chọn
     */
    public function setSelectedYear($year)
    {
        $yearDb = YearDatabase::where('year', $year)->first();
        
        if (!$yearDb) {
            throw new \Exception("Năm {$year} không tồn tại trong hệ thống");
        }

        if (!$yearDb->is_on_server) {
            throw new \Exception("Database năm {$year} đã được lưu trữ ngoại tuyến. Vui lòng import database để xem.");
        }

        session(['selected_year' => $year]);
        $this->selectedYear = $year;

        // Chuyển connection nếu không phải năm hiện tại
        if ($year != $this->getCurrentYear()) {
            $this->switchConnection($yearDb->database_name);
        }

        return true;
    }

    /**
     * Chuyển database connection
     */
    protected function switchConnection($databaseName)
    {
        Config::set('database.connections.year_archive', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $databaseName,
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ]);

        // Test connection
        try {
            DB::connection('year_archive')->getPdo();
        } catch (\Exception $e) {
            throw new \Exception("Không thể kết nối database {$databaseName}: " . $e->getMessage());
        }
    }

    /**
     * Lấy connection name dựa trên năm đang chọn
     */
    public function getConnection()
    {
        if ($this->selectedYear == $this->getCurrentYear()) {
            return 'mysql'; // Connection mặc định
        }
        return 'year_archive';
    }

    /**
     * Kiểm tra có đang xem dữ liệu năm cũ không
     */
    public function isViewingArchive()
    {
        return $this->selectedYear != $this->getCurrentYear();
    }

    /**
     * Lấy danh sách năm có sẵn
     */
    public function getAvailableYears()
    {
        return YearDatabase::getAvailableYears();
    }

    /**
     * Reset về năm hiện tại
     */
    public function resetToCurrentYear()
    {
        session()->forget('selected_year');
        $this->selectedYear = $this->getCurrentYear();
    }

    /**
     * Lấy thông tin database của năm
     */
    public function getYearInfo($year)
    {
        return YearDatabase::where('year', $year)->first();
    }
}
