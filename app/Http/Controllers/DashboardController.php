<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $period = $request->get('period', 'week');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $stats = $this->getStatistics($period, $fromDate, $toDate);
        
        return view('dashboard.index', compact('stats', 'period'));
    }

    public function getStats(Request $request)
    {
        $period = $request->get('period', 'week');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $stats = $this->getStatistics($period, $fromDate, $toDate);
        
        return response()->json($stats);
    }

    private function getStatistics($period, $fromDate = null, $toDate = null)
    {
        // Determine date range
        $dateRange = $this->getDateRange($period, $fromDate, $toDate);
        
        // Calculate sales revenue (excluding cancelled sales)
        $totalSales = Sale::whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
            ->where('payment_status', '!=', 'cancelled')
            ->sum('total_vnd');

        // Calculate remaining debt (unpaid and partial debts)
        $totalDebt = Debt::whereIn('status', ['unpaid', 'partial'])
            ->sum('debt_amount');

        // Count stock
        $stockPaintings = Painting::where('quantity', '>', 0)->sum('quantity');
        $stockSupplies = Supply::where('quantity', '>', 0)->sum('quantity');

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
            'sales' => $totalSales,
            'debt' => $totalDebt,
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
            $data = [];
            
            for ($i = 0; $i < 7; $i++) {
                $date = $dateRange['start']->copy()->addDays($i);
                $revenue = Sale::whereDate('sale_date', $date)
                    ->where('payment_status', '!=', 'cancelled')
                    ->sum('total_vnd');
                $data[] = $revenue;
            }
            
            return ['labels' => $labels, 'data' => $data];
        }

        if ($period === 'month') {
            $labels = [];
            $data = [];
            $daysInMonth = $dateRange['start']->daysInMonth;
            
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $labels[] = "Ngày {$i}";
                $date = $dateRange['start']->copy()->day($i);
                $revenue = Sale::whereDate('sale_date', $date)
                    ->where('payment_status', '!=', 'cancelled')
                    ->sum('total_vnd');
                $data[] = $revenue;
            }
            
            return ['labels' => $labels, 'data' => $data];
        }

        // year - group by month
        $labels = [];
        $data = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = "T{$i}";
            $monthStart = $dateRange['start']->copy()->month($i)->startOfMonth();
            $monthEnd = $dateRange['start']->copy()->month($i)->endOfMonth();
            
            $revenue = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])
                ->where('payment_status', '!=', 'cancelled')
                ->sum('total_vnd');
            $data[] = $revenue;
        }
        
        return ['labels' => $labels, 'data' => $data];
    }

    private function getProductDistribution($dateRange)
    {
        // Get sales items grouped by type
        $paintingSales = SaleItem::whereNotNull('painting_id')
            ->whereHas('sale', function($query) use ($dateRange) {
                $query->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
                    ->where('payment_status', '!=', 'cancelled');
            })
            ->sum('quantity');

        $supplySales = SaleItem::whereNotNull('supply_id')
            ->whereHas('sale', function($query) use ($dateRange) {
                $query->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
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

        $totalTransactions = Sale::whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
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

        // Count items sold this month
        $exportsThisMonth = SaleItem::whereHas('sale', function($query) use ($monthStart, $monthEnd) {
            $query->whereBetween('sale_date', [$monthStart, $monthEnd])
                ->where('payment_status', '!=', 'cancelled');
        })->sum('quantity');

        return [
            'imports_this_month' => $importsThisMonth,
            'exports_this_month' => $exportsThisMonth
        ];
    }

    private function getTopSellingProducts($dateRange)
    {
        // Get top selling paintings
        $topPaintings = SaleItem::select('painting_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_vnd) as total_revenue'))
            ->whereNotNull('painting_id')
            ->whereHas('sale', function($query) use ($dateRange) {
                $query->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
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
                    'image' => $painting->image ? asset('storage/' . $painting->image) : 'https://via.placeholder.com/100'
                ];
            }
        }

        // If less than 2 paintings, add supplies
        if (count($products) < 2) {
            $topSupplies = SaleItem::select('supply_id', DB::raw('SUM(supply_length) as total_quantity'), DB::raw('SUM(total_vnd) as total_revenue'))
                ->whereNotNull('supply_id')
                ->whereHas('sale', function($query) use ($dateRange) {
                    $query->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
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
                        'image' => 'https://via.placeholder.com/100'
                    ];
                }
            }
        }

        return $products;
    }
}
