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
     * Lấy danh sách năm có sẵn (trên server)
     */
    public function getAvailableYears()
    {
        return YearDatabase::getAvailableYears();
    }

    /**
     * Lấy tất cả năm (bao gồm offline)
     */
    public function getAllYears()
    {
        return YearDatabase::getAllYears();
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

    /**
     * Lấy đường dẫn file thực thi mysql/mysqldump
     */
    public function getMysqlExecutable($binary)
    {
        // Ưu tiên check trong .env
        $envPath = env('DB_BIN_PATH');
        if ($envPath) {
            $path = rtrim($envPath, '/\\') . DIRECTORY_SEPARATOR . $binary;
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $path .= '.exe';
            }
            if (file_exists($path)) {
                return '"' . $path . '"';
            }
        }

        // Check đường dẫn XAMPP mặc định (Windows)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $xamppPath = 'C:\\xampp\\mysql\\bin\\' . $binary . '.exe';
            if (file_exists($xamppPath)) {
                return '"' . $xamppPath . '"';
            }
        }

        // Check đường dẫn phổ biến trên Linux/Ubuntu
        $linuxPaths = [
            '/usr/bin/' . $binary,
            '/usr/local/bin/' . $binary,
            '/usr/local/mysql/bin/' . $binary,
        ];
        foreach ($linuxPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Fallback: trả về tên binary (dựa vào PATH của hệ thống)
        return $binary;
    }
}
