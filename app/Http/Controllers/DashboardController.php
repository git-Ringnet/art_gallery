<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Models\Debt;
use App\Models\Painting;
use App\Models\Supply;
use App\Models\Customer;
use App\Models\SaleItem;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Kiểm tra quyền truy cập dashboard
        $user = Auth::user();
        
        if (!$user || !$user->canAccess('dashboard')) {
            // Không có quyền dashboard → Hiển thị trang welcome
            return view('dashboard.no-access');
        }

        // Kiểm tra quyền lọc theo ngày
        $canFilterDate = Auth::user()->email === 'admin@example.com' || 
                        (Auth::user()->role && Auth::user()->role->getModulePermissions('dashboard') && 
                         Auth::user()->role->getModulePermissions('dashboard')->can_filter_by_date);

        $period = $request->get('period', 'week');
        $fromDate = $canFilterDate ? $request->get('from_date') : null;
        $toDate = $canFilterDate ? $request->get('to_date') : null;

        $stats = $this->getStatistics($period, $fromDate, $toDate);
        
        return view('dashboard.index', compact('stats', 'period', 'canFilterDate'));
    }



    public function getStats(Request $request)
    {
        // Kiểm tra quyền lọc theo ngày
        $canFilterDate = Auth::user()->email === 'admin@example.com' || 
                        (Auth::user()->role && Auth::user()->role->getModulePermissions('dashboard') && 
                         Auth::user()->role->getModulePermissions('dashboard')->can_filter_by_date);

        $period = $request->get('period', 'week');
        $fromDate = $canFilterDate ? $request->get('from_date') : null;
        $toDate = $canFilterDate ? $request->get('to_date') : null;

        $stats = $this->getStatistics($period, $fromDate, $toDate);
        
        return response()->json($stats);
    }


    private function getStatistics($period, $fromDate = null, $toDate = null)
    {
        // Determine date range
        $dateRange = $this->getDateRange($period, $fromDate, $toDate);
        
        // Calculate sales revenue (CHỈ phiếu đã duyệt - completed)
        $totalSalesUsd = Sale::whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
            ->where('sale_status', 'completed')
            ->where('payment_status', '!=', 'cancelled')
            ->sum('total_usd');
            
        $totalSalesVnd = Sale::whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
            ->where('sale_status', 'completed')
            ->where('payment_status', '!=', 'cancelled')
            ->sum('total_vnd');

        // Calculate remaining debt (RIÊNG USD và VND)
        $sales = Sale::whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
            ->where('sale_status', 'completed')
            ->where('payment_status', '!=', 'cancelled')
            ->get();
            
        $totalDebtUsd = $sales->sum(function($sale) {
            return $sale->debt_usd ?? 0;
        });
        
        $totalDebtVnd = $sales->sum(function($sale) {
            return $sale->debt_vnd ?? 0;
        });

        // Count stock - filter by created_at within date range
        // Tồn tranh: số lượng tranh được tạo trong khoảng thời gian
        $stockPaintings = Painting::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('quantity', '>', 0)
            ->sum('quantity');
            
        // Tồn vật tư: số lượng vật tư được tạo trong khoảng thời gian
        $stockSupplies = Supply::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('quantity', '>', 0)
            ->sum('quantity');

        // Get revenue chart data
        $revenueChart = $this->getRevenueChartData($period, $fromDate, $toDate);

        // Get product distribution
        $productDistribution = $this->getProductDistribution($dateRange);

        // Get customer stats
        $customerStats = $this->getCustomerStats($dateRange);

        // Get inventory stats
        $inventoryStats = $this->getInventoryStats();

        // Get top selling products
        $topProducts = $this->getTopSellingProducts($dateRange);

        return [
            'sales_usd' => $totalSalesUsd,
            'sales_vnd' => $totalSalesVnd,
            'debt_usd' => $totalDebtUsd,
            'debt_vnd' => $totalDebtVnd,
            'stock_paintings' => $stockPaintings,
            'stock_supplies' => $stockSupplies,
            'revenue_chart' => $revenueChart,
            'product_distribution' => $productDistribution,
            'customer_stats' => $customerStats,
            'inventory_stats' => $inventoryStats,
            'top_products' => $topProducts
        ];
    }

    private function getDateRange($period, $fromDate = null, $toDate = null)
    {
        if ($fromDate && $toDate) {
            return [
                'start' => Carbon::parse($fromDate)->startOfDay(),
                'end' => Carbon::parse($toDate)->endOfDay()
            ];
        }

        $now = Carbon::now();
        
        switch ($period) {
            case 'week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek()
                ];
            case 'month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
            case 'year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear()
                ];
            default:
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek()
                ];
        }
    }

    private function getRevenueChartData($period, $fromDate, $toDate)
    {
        $dateRange = $this->getDateRange($period, $fromDate, $toDate);
        
        if ($period === 'week') {
            $labels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
            $dataVnd = [];
            $dataUsd = [];
            
            for ($i = 0; $i < 7; $i++) {
                $date = $dateRange['start']->copy()->addDays($i);
                $revenueVnd = Sale::whereDate('sale_date', $date)
                    ->where('sale_status', 'completed')
                    ->where('payment_status', '!=', 'cancelled')
                    ->sum('total_vnd');
                $revenueUsd = Sale::whereDate('sale_date', $date)
                    ->where('sale_status', 'completed')
                    ->where('payment_status', '!=', 'cancelled')
                    ->sum('total_usd');
                $dataVnd[] = $revenueVnd;
                $dataUsd[] = $revenueUsd;
            }
            
            return ['labels' => $labels, 'data_vnd' => $dataVnd, 'data_usd' => $dataUsd];
        }

        if ($period === 'month') {
            $labels = [];
            $dataVnd = [];
            $dataUsd = [];
            $daysInMonth = $dateRange['start']->daysInMonth;
            
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $labels[] = "Ngày {$i}";
                $date = $dateRange['start']->copy()->day($i);
                $revenueVnd = Sale::whereDate('sale_date', $date)
                    ->where('sale_status', 'completed')
                    ->where('payment_status', '!=', 'cancelled')
                    ->sum('total_vnd');
                $revenueUsd = Sale::whereDate('sale_date', $date)
                    ->where('sale_status', 'completed')
                    ->where('payment_status', '!=', 'cancelled')
                    ->sum('total_usd');
                $dataVnd[] = $revenueVnd;
                $dataUsd[] = $revenueUsd;
            }
            
            return ['labels' => $labels, 'data_vnd' => $dataVnd, 'data_usd' => $dataUsd];
        }

        // year - group by month
        $labels = [];
        $dataVnd = [];
        $dataUsd = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = "T{$i}";
            $monthStart = $dateRange['start']->copy()->month($i)->startOfMonth();
            $monthEnd = $dateRange['start']->copy()->month($i)->endOfMonth();
            
            $revenueVnd = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])
                ->where('sale_status', 'completed')
                ->where('payment_status', '!=', 'cancelled')
                ->sum('total_vnd');
            $revenueUsd = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])
                ->where('sale_status', 'completed')
                ->where('payment_status', '!=', 'cancelled')
                ->sum('total_usd');
            $dataVnd[] = $revenueVnd;
            $dataUsd[] = $revenueUsd;
        }
        
        return ['labels' => $labels, 'data_vnd' => $dataVnd, 'data_usd' => $dataUsd];
    }

    private function getProductDistribution($dateRange)
    {
        // Get sales items grouped by type (CHỈ phiếu đã duyệt - completed)
        $paintingSales = SaleItem::whereNotNull('painting_id')
            ->whereHas('sale', function($query) use ($dateRange) {
                $query->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
                    ->where('sale_status', 'completed')
                    ->where('payment_status', '!=', 'cancelled');
            })
            ->sum('quantity');

        $supplySales = SaleItem::whereNotNull('supply_id')
            ->whereHas('sale', function($query) use ($dateRange) {
                $query->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
                    ->where('sale_status', 'completed')
                    ->where('payment_status', '!=', 'cancelled');
            })
            ->sum(DB::raw('CAST(supply_length as UNSIGNED)'));

        $labels = [];
        $data = [];

        if ($paintingSales > 0) {
            $labels[] = 'Tranh';
            $data[] = $paintingSales;
        }

        if ($supplySales > 0) {
            $labels[] = 'Vật tư (mét)';
            $data[] = $supplySales;
        }

        // If no data, show placeholder
        if (empty($data)) {
            $labels = ['Chưa có dữ liệu'];
            $data = [1];
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function getCustomerStats($dateRange)
    {
        $newCustomers = Customer::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // CHỈ đếm phiếu đã duyệt (completed)
        $totalTransactions = Sale::whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
            ->where('sale_status', 'completed')
            ->where('payment_status', '!=', 'cancelled')
            ->count();

        return [
            'new_customers' => $newCustomers,
            'total_transactions' => $totalTransactions
        ];
    }

    private function getInventoryStats()
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        // Count items imported this month (from inventory_transactions or sales)
        $importsThisMonth = Painting::whereBetween('created_at', [$monthStart, $monthEnd])->count() +
                           Supply::whereBetween('created_at', [$monthStart, $monthEnd])->count();

        // Count items sold this month (CHỈ phiếu đã duyệt - completed)
        $exportsThisMonth = SaleItem::whereHas('sale', function($query) use ($monthStart, $monthEnd) {
            $query->whereBetween('sale_date', [$monthStart, $monthEnd])
                ->where('sale_status', 'completed')
                ->where('payment_status', '!=', 'cancelled');
        })->sum('quantity');

        return [
            'imports_this_month' => $importsThisMonth,
            'exports_this_month' => $exportsThisMonth
        ];
    }

    private function getTopSellingProducts($dateRange)
    {
        // Get top selling paintings (CHỈ phiếu đã duyệt - completed)
        $topPaintings = SaleItem::select('painting_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_vnd) as total_revenue'), DB::raw('SUM(total_usd) as total_revenue_usd'))
            ->whereNotNull('painting_id')
            ->whereHas('sale', function($query) use ($dateRange) {
                $query->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
                    ->where('sale_status', 'completed')
                    ->where('payment_status', '!=', 'cancelled');
            })
            ->groupBy('painting_id')
            ->orderByDesc('total_quantity')
            ->limit(2)
            ->get();

        $products = [];
        foreach ($topPaintings as $item) {
            $painting = Painting::find($item->painting_id);
            if ($painting) {
                $products[] = [
                    'name' => $painting->name ?? 'Tranh',
                    'type' => 'Tranh',
                    'quantity' => $item->total_quantity,
                    'revenue' => $item->total_revenue,
                    'revenue_usd' => $item->total_revenue_usd,
                    'image' => $painting->image ? asset('storage/' . $painting->image) : 'https://via.placeholder.com/100'
                ];
            }
        }

        // If less than 2 paintings, add supplies
        if (count($products) < 2) {
            $topSupplies = SaleItem::select('supply_id', DB::raw('SUM(supply_length) as total_quantity'), DB::raw('SUM(total_vnd) as total_revenue'), DB::raw('SUM(total_usd) as total_revenue_usd'))
                ->whereNotNull('supply_id')
                ->whereHas('sale', function($query) use ($dateRange) {
                    $query->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
                        ->where('sale_status', 'completed')
                        ->where('payment_status', '!=', 'cancelled');
                })
                ->groupBy('supply_id')
                ->orderByDesc('total_quantity')
                ->limit(2 - count($products))
                ->get();

            foreach ($topSupplies as $item) {
                $supply = Supply::find($item->supply_id);
                if ($supply) {
                    $products[] = [
                        'name' => $supply->name ?? 'Vật tư',
                        'type' => 'Vật tư',
                        'quantity' => round($item->total_quantity, 2),
                        'revenue' => $item->total_revenue,
                        'revenue_usd' => $item->total_revenue_usd,
                        'image' => 'https://via.placeholder.com/100'
                    ];
                }
            }
        }

        return $products;
    }
}
