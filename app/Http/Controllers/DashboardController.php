<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // Mock data - replace with actual database queries
        $multipliers = ['week' => 1, 'month' => 4, 'year' => 52];
        $m = $multipliers[$period] ?? 1;

        return [
            'sales' => 15250000 * $m,
            'debt' => 8500000 / ($period === 'year' ? 1 : ($period === 'month' ? 2 : 4)),
            'stock_paintings' => 89,
            'stock_supplies' => 156,
            'revenue_chart' => $this->getRevenueChartData($period, $fromDate, $toDate),
            'product_distribution' => [
                'labels' => ['Tranh sơn dầu', 'Tranh canvas', 'Khung gỗ'],
                'data' => [45, 25, 30]
            ],
            'customer_stats' => [
                'new_customers' => 24,
                'total_transactions' => 342
            ],
            'inventory_stats' => [
                'imports_this_month' => 156,
                'exports_this_month' => 89
            ]
        ];
    }

    private function getRevenueChartData($period, $fromDate, $toDate)
    {
        if ($period === 'week') {
            return [
                'labels' => ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
                'data' => [1200000, 1900000, 3000000, 5000000, 2000000, 3000000, 4500000]
            ];
        }

        if ($period === 'month') {
            $labels = [];
            $data = [];
            for ($i = 1; $i <= 12; $i++) {
                $labels[] = "T{$i}";
                $data[] = (12 + $i * 2) * 300000;
            }
            return ['labels' => $labels, 'data' => $data];
        }

        // year
        return [
            'labels' => ['2021', '2022', '2023', '2024'],
            'data' => [120000000, 150000000, 180000000, 210000000]
        ];
    }
}
