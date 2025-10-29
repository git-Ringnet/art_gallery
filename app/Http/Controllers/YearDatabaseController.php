<?php

namespace App\Http\Controllers;

use App\Services\YearDatabaseService;
use Illuminate\Http\Request;

class YearDatabaseController extends Controller
{
    protected $yearService;

    public function __construct(YearDatabaseService $yearService)
    {
        $this->yearService = $yearService;
    }

    /**
     * Hiển thị trang quản lý database theo năm
     */
    public function index()
    {
        $currentYear = \App\Models\YearDatabase::getCurrentYear();
        $selectedYear = $this->yearService->getSelectedYear();
        $isViewingArchive = $this->yearService->isViewingArchive();
        $availableYears = \App\Models\YearDatabase::getAvailableYears();
        $allYears = \App\Models\YearDatabase::getAllYears();

        return view('year-database.index', compact(
            'currentYear',
            'selectedYear',
            'isViewingArchive',
            'availableYears',
            'allYears'
        ));
    }

    /**
     * Chuyển sang năm khác
     */
    public function switchYear(Request $request)
    {
        $year = $request->input('year');

        try {
            $this->yearService->setSelectedYear($year);
            
            return response()->json([
                'success' => true,
                'message' => "Đã chuyển sang xem dữ liệu năm {$year}",
                'year' => $year,
                'is_archive' => $this->yearService->isViewingArchive(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset về năm hiện tại
     */
    public function resetYear()
    {
        $this->yearService->resetToCurrentYear();
        
        return response()->json([
            'success' => true,
            'message' => 'Đã quay lại năm hiện tại',
            'year' => $this->yearService->getCurrentYear(),
        ]);
    }

    /**
     * Lấy thông tin năm hiện tại
     */
    public function getCurrentInfo()
    {
        return response()->json([
            'current_year' => $this->yearService->getCurrentYear(),
            'selected_year' => $this->yearService->getSelectedYear(),
            'is_viewing_archive' => $this->yearService->isViewingArchive(),
            'available_years' => $this->yearService->getAvailableYears(),
        ]);
    }
}
