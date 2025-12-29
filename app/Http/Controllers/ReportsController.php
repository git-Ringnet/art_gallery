<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\ExchangeRate;
use App\Models\Showroom;
use App\Models\User;
use App\Models\Customer;
use App\Models\Painting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    /**
     * Reports index - list all available reports
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Display daily cash collection report
     */
    public function dailyCashCollection(Request $request)
    {
        // Kiểm tra quyền truy cập module reports
        $user = Auth::user();
        $permission = $user->role?->rolePermissions()
            ->whereHas('permission', function ($q) {
                $q->where('module', 'reports');
            })
            ->first();

        if (!$permission || !$permission->can_view) {
            abort(403, 'Bạn không có quyền xem báo cáo');
        }

        // Lấy khoảng thời gian từ request hoặc mặc định là hôm nay
        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->input('from_date'))
            : Carbon::today();

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->input('to_date'))
            : Carbon::today();

        // Đảm bảo from_date <= to_date
        if ($fromDate->gt($toDate)) {
            $temp = $fromDate;
            $fromDate = $toDate;
            $toDate = $temp;
        }

        // Lấy filters từ request
        $showroomId = $request->input('showroom_id');
        $employeeId = $request->input('employee_id');
        $customerId = $request->input('customer_id');

        // Áp dụng phân quyền data_scope
        $dataScope = $permission->data_scope ?? 'all';
        $allowedShowrooms = $permission->allowed_showrooms;

        // Lấy các quyền lọc
        $canFilterByShowroom = $permission->can_filter_by_showroom ?? true;
        $canFilterByUser = $permission->can_filter_by_user ?? true;
        $canFilterByDate = $permission->can_filter_by_date ?? true;
        $canPrint = $permission->can_print ?? true;

        // Nếu không có quyền lọc theo showroom, bỏ qua filter showroom
        if (!$canFilterByShowroom) {
            $showroomId = null;
        }

        // Nếu không có quyền lọc theo nhân viên, bỏ qua filter employee
        if (!$canFilterByUser) {
            $employeeId = null;
        }

        // Lấy danh sách showrooms dựa trên quyền
        if ($dataScope === 'showroom' && $allowedShowrooms) {
            $showrooms = Showroom::whereIn('id', $allowedShowrooms)->orderBy('name')->get();
            if ($showroomId && !in_array($showroomId, $allowedShowrooms)) {
                $showroomId = null;
            }
        } else {
            $showrooms = Showroom::orderBy('name')->get();
        }

        // Lấy danh sách nhân viên
        $employees = User::orderBy('name')->get();

        // Lấy danh sách khách hàng
        $customers = Customer::orderBy('name')->get();

        // Lấy thông tin showroom nếu được chọn
        $selectedShowroom = null;
        if ($showroomId) {
            $selectedShowroom = Showroom::find($showroomId);
        }

        // Lấy tỷ giá từ request
        $exchangeRateInput = $request->input('exchange_rate');
        if (!$exchangeRateInput || $exchangeRateInput == '') {
            $exchangeRate = 1;
        } else {
            $cleanRate = str_replace(',', '', $exchangeRateInput);
            if (strpos($cleanRate, '.') !== false) {
                $parts = explode('.', $cleanRate);
                if (count($parts) == 2 && strlen($parts[1]) == 3) {
                    $cleanRate = $parts[0] . $parts[1];
                }
            }
            $exchangeRate = (float) $cleanRate;
            if ($exchangeRate > 0 && $exchangeRate < 1000) {
                $exchangeRate = $exchangeRate * 1000;
            }
        }

        // Query payments
        $fromDateTime = $fromDate->format('Y-m-d') . ' 00:00:00';
        $toDateTime = $toDate->format('Y-m-d') . ' 23:59:59';

        $paymentsQuery = Payment::with(['sale.customer', 'sale.showroom', 'sale.items.painting', 'sale.items.supply', 'sale.items.frame', 'sale.user'])
            ->whereBetween('payment_date', [$fromDateTime, $toDateTime])
            ->where('transaction_type', 'sale_payment')
            ->whereHas('sale', function ($q) use ($showroomId, $employeeId, $customerId, $dataScope, $allowedShowrooms, $user) {
                if ($showroomId) {
                    $q->where('showroom_id', $showroomId);
                } elseif ($dataScope === 'showroom' && $allowedShowrooms) {
                    $q->whereIn('showroom_id', $allowedShowrooms);
                }

                if ($employeeId) {
                    $q->where('user_id', $employeeId);
                } elseif ($dataScope === 'own') {
                    $q->where('user_id', $user->id);
                }

                if ($customerId) {
                    $q->where('customer_id', $customerId);
                }
            });

        // Filter theo loại thanh toán
        $paymentType = $request->input('payment_type');
        if ($paymentType === 'cash') {
            // Chỉ tiền mặt
            $paymentsQuery->where('payment_method', 'cash');
        } elseif ($paymentType === 'card_transfer') {
            // Thẻ + Chuyển khoản (không phải cash)
            $paymentsQuery->where('payment_method', '!=', 'cash');
        }

        $paymentsQuery = $paymentsQuery->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $reportData = [];
        $totalDepositUsd = 0;
        $totalDepositVnd = 0;
        $totalAdjustmentUsd = 0;
        $totalAdjustmentVnd = 0;
        $totalCollectionUsd = 0;
        $totalCollectionVnd = 0;
        $cashCollectionVnd = 0;
        $cardCollectionVnd = 0;

        foreach ($paymentsQuery as $payment) {
            $sale = $payment->sale;
            $firstItem = $sale->items->first();

            if (!$firstItem) {
                continue;
            }

            $idCode = '';
            if ($firstItem->painting_id) {
                $idCode = $firstItem->painting->code ?? 'N/A';
            } elseif ($firstItem->supply_id) {
                $idCode = $firstItem->supply->code ?? 'SUP' . $firstItem->supply_id;
            } elseif ($firstItem->frame_id) {
                $idCode = 'FRAME' . $firstItem->frame_id;
            }

            $saleDepositUsd = 0;
            $saleDepositVnd = 0;
            $saleAdjustmentUsd = 0;
            $saleAdjustmentVnd = 0;

            foreach ($sale->items as $item) {
                if ($item->is_returned)
                    continue;

                $subtotal = $item->quantity * ($item->currency == 'USD' ? $item->price_usd : $item->price_vnd);

                if ($item->currency == 'USD') {
                    $saleDepositUsd += $subtotal;
                    if ($item->discount_percent > 0) {
                        $discount = $subtotal * ($item->discount_percent / 100);
                        $saleAdjustmentUsd += -$discount;
                    }
                } else {
                    $saleDepositVnd += $subtotal;
                    if ($item->discount_percent > 0) {
                        $discount = $subtotal * ($item->discount_percent / 100);
                        $saleAdjustmentVnd += -$discount;
                    }
                }
            }

            $rowData = [
                'invoice_code' => $sale->invoice_code,
                'id_code' => $idCode,
                'customer_name' => $sale->customer->name,
                'deposit_usd' => $saleDepositUsd,
                'deposit_vnd' => $saleDepositVnd,
                'adjustment_usd' => $saleAdjustmentUsd,
                'adjustment_vnd' => $saleAdjustmentVnd,
                'collection_usd' => $payment->payment_usd ?? 0,
                'collection_vnd' => $payment->payment_vnd ?? 0,
                'collection_adjustment_usd' => 0,
                'collection_adjustment_vnd' => 0,
            ];

            $reportData[] = $rowData;

            $totalDepositUsd += $saleDepositUsd;
            $totalDepositVnd += $saleDepositVnd;
            $totalAdjustmentUsd += $saleAdjustmentUsd;
            $totalAdjustmentVnd += $saleAdjustmentVnd;
            $totalCollectionUsd += ($payment->payment_usd ?? 0);
            $totalCollectionVnd += ($payment->payment_vnd ?? 0);

            $paymentUsd = $payment->payment_usd ?? 0;
            $paymentVnd = $payment->payment_vnd ?? 0;
            $collectionVndForCashCard = ($paymentUsd * $exchangeRate) + $paymentVnd;

            if ($payment->payment_method == 'cash') {
                $cashCollectionVnd += $collectionVndForCashCard;
            } else {
                $cardCollectionVnd += $collectionVndForCashCard;
            }
        }

        if ($exchangeRate > 1) {
            $totalDepositTotalVnd = ($totalDepositUsd * $exchangeRate) + $totalDepositVnd;
            $totalAdjustmentTotalVnd = ($totalAdjustmentUsd * $exchangeRate) + $totalAdjustmentVnd;
            $grandTotalVnd = ($totalCollectionUsd * $exchangeRate) + $totalCollectionVnd;
        } else {
            $totalDepositTotalVnd = $totalDepositVnd;
            $totalAdjustmentTotalVnd = $totalAdjustmentVnd;
            $grandTotalVnd = $totalCollectionVnd;
        }

        $totalCollectionAdjustmentUsd = 0;
        $totalCollectionAdjustmentVnd = 0;

        return view('reports.daily-cash-collection', compact(
            'reportData',
            'fromDate',
            'toDate',
            'exchangeRate',
            'showrooms',
            'showroomId',
            'selectedShowroom',
            'employees',
            'customers',
            'canFilterByShowroom',
            'canFilterByUser',
            'canFilterByDate',
            'canPrint',
            'totalDepositUsd',
            'totalDepositVnd',
            'totalDepositTotalVnd',
            'totalAdjustmentUsd',
            'totalAdjustmentVnd',
            'totalAdjustmentTotalVnd',
            'totalCollectionUsd',
            'totalCollectionVnd',
            'totalCollectionAdjustmentUsd',
            'totalCollectionAdjustmentVnd',
            'cashCollectionVnd',
            'cardCollectionVnd',
            'grandTotalVnd'
        ));
    }

    /**
     * Monthly Sales Report - Báo cáo thống kê bán hàng tháng
     */
    public function monthlySales(Request $request)
    {
        $user = Auth::user();
        $permission = $user->role?->rolePermissions()
            ->whereHas('permission', function ($q) {
                $q->where('module', 'reports');
            })
            ->first();

        if (!$permission || !$permission->can_view) {
            abort(403, 'Bạn không có quyền xem báo cáo');
        }

        // Mặc định là tháng hiện tại
        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->input('from_date'))
            : Carbon::now()->startOfMonth();

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->input('to_date'))
            : Carbon::now()->endOfMonth();

        if ($fromDate->gt($toDate)) {
            $temp = $fromDate;
            $fromDate = $toDate;
            $toDate = $temp;
        }

        $showroomId = $request->input('showroom_id');
        $employeeId = $request->input('employee_id');

        $dataScope = $permission->data_scope ?? 'all';
        $allowedShowrooms = $permission->allowed_showrooms;
        $canFilterByShowroom = $permission->can_filter_by_showroom ?? true;
        $canFilterByUser = $permission->can_filter_by_user ?? true;
        $canFilterByDate = $permission->can_filter_by_date ?? true;
        $canPrint = $permission->can_print ?? true;

        if (!$canFilterByShowroom)
            $showroomId = null;
        if (!$canFilterByUser)
            $employeeId = null;

        if ($dataScope === 'showroom' && $allowedShowrooms) {
            $showrooms = Showroom::whereIn('id', $allowedShowrooms)->orderBy('name')->get();
            if ($showroomId && !in_array($showroomId, $allowedShowrooms)) {
                $showroomId = null;
            }
        } else {
            $showrooms = Showroom::orderBy('name')->get();
        }

        $employees = User::orderBy('name')->get();

        $selectedShowroom = $showroomId ? Showroom::find($showroomId) : null;

        // Lấy tỷ giá
        $exchangeRateInput = $request->input('exchange_rate');
        $exchangeRate = 1;
        if ($exchangeRateInput && $exchangeRateInput != '') {
            $cleanRate = str_replace(',', '', $exchangeRateInput);
            if (strpos($cleanRate, '.') !== false) {
                $parts = explode('.', $cleanRate);
                if (count($parts) == 2 && strlen($parts[1]) == 3) {
                    $cleanRate = $parts[0] . $parts[1];
                }
            }
            $exchangeRate = (float) $cleanRate;
            if ($exchangeRate > 0 && $exchangeRate < 1000) {
                $exchangeRate = $exchangeRate * 1000;
            }
        }

        // Query sales - chỉ lấy phiếu đã duyệt (completed)
        $selectedYear = session('selected_year', date('Y'));

        $salesQuery = Sale::with(['customer', 'showroom', 'user', 'items.painting', 'items.supply', 'items.frame'])
            ->where('sale_status', 'completed')
            ->where('year', $selectedYear)
            ->whereBetween('sale_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')]);

        if ($showroomId) {
            $salesQuery->where('showroom_id', $showroomId);
        } elseif ($dataScope === 'showroom' && $allowedShowrooms) {
            $salesQuery->whereIn('showroom_id', $allowedShowrooms);
        }

        if ($employeeId) {
            $salesQuery->where('user_id', $employeeId);
        } elseif ($dataScope === 'own') {
            $salesQuery->where('user_id', $user->id);
        }

        $sales = $salesQuery->orderBy('sale_date')->orderBy('id')->get();

        $reportData = [];
        $totalUsd = 0;
        $totalVnd = 0;
        $totalPaidVnd = 0;
        $totalDebtVnd = 0;
        $totalItems = 0;

        foreach ($sales as $sale) {
            $firstItem = $sale->items->first();
            $idCode = '';
            $itemDescription = '';

            if ($firstItem) {
                if ($firstItem->painting_id && $firstItem->painting) {
                    $idCode = $firstItem->painting->code ?? '';
                    $itemDescription = $firstItem->painting->name ?? $firstItem->description;
                } elseif ($firstItem->supply_id && $firstItem->supply) {
                    $idCode = $firstItem->supply->code ?? '';
                    $itemDescription = $firstItem->supply->name ?? $firstItem->description;
                } elseif ($firstItem->frame_id) {
                    $idCode = 'FRAME' . $firstItem->frame_id;
                    $itemDescription = $firstItem->description;
                } else {
                    $itemDescription = $firstItem->description;
                }
            }

            $itemCount = $sale->items->sum('quantity');

            $reportData[] = [
                'sale_date' => $sale->sale_date->format('d/m/Y'),
                'invoice_code' => $sale->invoice_code,
                'id_code' => $idCode,
                'customer_name' => $sale->customer->name ?? 'Khách lẻ',
                'item_description' => $itemDescription,
                'item_count' => $itemCount,
                'total_usd' => $sale->total_usd,
                'total_vnd' => $sale->total_vnd,
                'paid_vnd' => $sale->paid_amount ?? 0,
                'debt_vnd' => $sale->debt_amount ?? 0,
                'showroom' => $sale->showroom->name ?? '',
                'employee' => $sale->user->name ?? '',
            ];

            $totalUsd += $sale->total_usd;
            $totalVnd += $sale->total_vnd;
            $totalPaidVnd += ($sale->paid_amount ?? 0);
            $totalDebtVnd += ($sale->debt_amount ?? 0);
            $totalItems += $itemCount;
        }

        // Tính tổng quy đổi VND
        if ($exchangeRate > 1) {
            $grandTotalVnd = ($totalUsd * $exchangeRate) + $totalVnd;
        } else {
            $grandTotalVnd = $totalVnd;
        }
        $grandPaidVnd = $totalPaidVnd;
        $grandDebtVnd = $totalDebtVnd;

        return view('reports.monthly-sales', compact(
            'reportData',
            'fromDate',
            'toDate',
            'exchangeRate',
            'showrooms',
            'showroomId',
            'selectedShowroom',
            'employees',
            'employeeId',
            'canFilterByShowroom',
            'canFilterByUser',
            'canFilterByDate',
            'canPrint',
            'totalUsd',
            'totalVnd',
            'totalPaidVnd',
            'totalDebtVnd',
            'totalItems',
            'grandTotalVnd',
            'grandPaidVnd',
            'grandDebtVnd'
        ));
    }

    /**
     * Debt Report - Báo cáo công nợ
     */
    public function debtReport(Request $request)
    {
        $user = Auth::user();
        $permission = $user->role?->rolePermissions()
            ->whereHas('permission', function ($q) {
                $q->where('module', 'reports');
            })
            ->first();

        if (!$permission || !$permission->can_view) {
            abort(403, 'Bạn không có quyền xem báo cáo');
        }

        // Mặc định là lũy kế (tất cả công nợ còn lại)
        $reportType = $request->input('report_type', 'cumulative'); // 'month' hoặc 'cumulative'

        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->input('from_date'))
            : Carbon::now()->startOfYear(); // Từ đầu năm

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->input('to_date'))
            : Carbon::now(); // Đến hôm nay

        if ($fromDate->gt($toDate)) {
            $temp = $fromDate;
            $fromDate = $toDate;
            $toDate = $temp;
        }

        $showroomId = $request->input('showroom_id');
        $customerId = $request->input('customer_id');

        $dataScope = $permission->data_scope ?? 'all';
        $allowedShowrooms = $permission->allowed_showrooms;
        $canFilterByShowroom = $permission->can_filter_by_showroom ?? true;
        $canFilterByDate = $permission->can_filter_by_date ?? true;
        $canPrint = $permission->can_print ?? true;

        if (!$canFilterByShowroom)
            $showroomId = null;

        if ($dataScope === 'showroom' && $allowedShowrooms) {
            $showrooms = Showroom::whereIn('id', $allowedShowrooms)->orderBy('name')->get();
            if ($showroomId && !in_array($showroomId, $allowedShowrooms)) {
                $showroomId = null;
            }
        } else {
            $showrooms = Showroom::orderBy('name')->get();
        }

        $customers = Customer::orderBy('name')->get();
        $selectedShowroom = $showroomId ? Showroom::find($showroomId) : null;

        // Lấy tỷ giá
        $exchangeRateInput = $request->input('exchange_rate');
        $exchangeRate = 1;
        if ($exchangeRateInput && $exchangeRateInput != '') {
            $cleanRate = str_replace(',', '', $exchangeRateInput);
            if (strpos($cleanRate, '.') !== false) {
                $parts = explode('.', $cleanRate);
                if (count($parts) == 2 && strlen($parts[1]) == 3) {
                    $cleanRate = $parts[0] . $parts[1];
                }
            }
            $exchangeRate = (float) $cleanRate;
            if ($exchangeRate > 0 && $exchangeRate < 1000) {
                $exchangeRate = $exchangeRate * 1000;
            }
        }

        $selectedYear = session('selected_year', date('Y'));

        // Query sales có công nợ
        // Lưu ý: debt_usd, debt_vnd là accessor nên không thể dùng trong where
        // Lấy tất cả phiếu completed chưa thanh toán đủ (payment_status != 'paid')
        // HOẶC có total_usd > 0 (vì payment_status có thể không chính xác cho USD)
        $salesQuery = Sale::with(['customer', 'showroom', 'user', 'items.painting', 'payments'])
            ->where('sale_status', 'completed')
            ->where('year', $selectedYear)
            ->where(function ($q) {
                // Lấy phiếu chưa thanh toán đủ HOẶC có USD (để kiểm tra bằng accessor)
                $q->where('payment_status', '!=', 'paid')
                    ->orWhere('total_usd', '>', 0);
            });

        if ($reportType === 'cumulative') {
            // Công nợ từ đầu đến hết ngày được chọn
            $salesQuery->whereDate('sale_date', '<=', $toDate->format('Y-m-d'));
        } else {
            // Công nợ trong khoảng thời gian
            $salesQuery->whereBetween('sale_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')]);
        }

        if ($showroomId) {
            $salesQuery->where('showroom_id', $showroomId);
        } elseif ($dataScope === 'showroom' && $allowedShowrooms) {
            $salesQuery->whereIn('showroom_id', $allowedShowrooms);
        }

        if ($customerId) {
            $salesQuery->where('customer_id', $customerId);
        }

        if ($dataScope === 'own') {
            $salesQuery->where('user_id', $user->id);
        }

        $sales = $salesQuery->orderBy('sale_date')->orderBy('id')->get();

        $reportData = [];
        $totalSaleUsd = 0;
        $totalSaleVnd = 0;
        $totalPaidUsd = 0;
        $totalPaidVnd = 0;
        $totalDebtUsd = 0;
        $totalDebtVnd = 0;

        foreach ($sales as $sale) {
            $firstItem = $sale->items->first();
            $idCode = '';

            if ($firstItem && $firstItem->painting_id && $firstItem->painting) {
                $idCode = $firstItem->painting->code ?? '';
            }

            // Xác định loại hóa đơn
            $isUsdOnly = $sale->total_usd > 0 && $sale->total_vnd <= 0;
            $isVndOnly = $sale->total_vnd > 0 && $sale->total_usd <= 0;
            $isMixed = $sale->total_usd > 0 && $sale->total_vnd > 0;

            // Sử dụng accessor để lấy debt_usd và debt_vnd
            $debtUsd = $sale->debt_usd ?? 0;
            $debtVnd = $sale->debt_vnd ?? 0;
            $paidUsd = $sale->paid_usd ?? 0;
            $paidVnd = $sale->paid_vnd ?? 0;

            // Chỉ thêm vào báo cáo nếu còn nợ (USD hoặc VND)
            if ($debtUsd > 0.01 || $debtVnd > 1) {
                $reportData[] = [
                    'sale_date' => $sale->sale_date->format('d/m/Y'),
                    'invoice_code' => $sale->invoice_code,
                    'id_code' => $idCode,
                    'customer_name' => $sale->customer->name ?? 'Khách lẻ',
                    'customer_phone' => $sale->customer->phone ?? '',
                    'total_usd' => $sale->total_usd,
                    'total_vnd' => $sale->total_vnd,
                    'paid_usd' => $paidUsd,
                    'paid_vnd' => $paidVnd,
                    'debt_usd' => $debtUsd,
                    'debt_vnd' => $debtVnd,
                    'showroom' => $sale->showroom->name ?? '',
                    // Thêm loại hóa đơn để view hiển thị đúng
                    'is_usd_only' => $isUsdOnly,
                    'is_vnd_only' => $isVndOnly,
                    'is_mixed' => $isMixed,
                ];

                // Tính tổng - cộng tất cả USD và VND riêng biệt
                $totalSaleUsd += $sale->total_usd;
                $totalSaleVnd += $sale->total_vnd;

                // Đơn USD: debt/paid là USD
                // Đơn VND: debt/paid là VND
                // Đơn hỗn hợp: có cả USD và VND
                if ($isUsdOnly) {
                    $totalPaidUsd += $paidUsd;
                    $totalDebtUsd += $debtUsd;
                } elseif ($isVndOnly) {
                    $totalPaidVnd += $paidVnd;
                    $totalDebtVnd += $debtVnd;
                } else {
                    // Đơn hỗn hợp
                    $totalPaidUsd += $paidUsd;
                    $totalPaidVnd += $paidVnd;
                    $totalDebtUsd += $debtUsd;
                    $totalDebtVnd += $debtVnd;
                }
            }
        }

        // Tính tổng quy đổi VND
        // grandTotalVnd = (tất cả USD * tỷ giá) + tất cả VND
        if ($exchangeRate > 1) {
            $grandTotalVnd = ($totalSaleUsd * $exchangeRate) + $totalSaleVnd;
            $grandPaidVnd = ($totalPaidUsd * $exchangeRate) + $totalPaidVnd;
            $grandDebtVnd = ($totalDebtUsd * $exchangeRate) + $totalDebtVnd;
        } else {
            // Không có tỷ giá: không thể quy đổi
            $grandTotalVnd = $totalSaleVnd;
            $grandPaidVnd = $totalPaidVnd;
            $grandDebtVnd = $totalDebtVnd;
        }

        return view('reports.debt-report', compact(
            'reportData',
            'reportType',
            'fromDate',
            'toDate',
            'exchangeRate',
            'showrooms',
            'showroomId',
            'selectedShowroom',
            'customers',
            'customerId',
            'canFilterByShowroom',
            'canFilterByDate',
            'canPrint',
            'totalSaleUsd',
            'totalSaleVnd',
            'totalPaidUsd',
            'totalPaidVnd',
            'totalDebtUsd',
            'totalDebtVnd',
            'grandTotalVnd',
            'grandPaidVnd',
            'grandDebtVnd'
        ));
    }

    /**
     * Stock Import Report - Báo cáo nhập stock tháng
     */
    public function stockImport(Request $request)
    {
        $user = Auth::user();
        $permission = $user->role?->rolePermissions()
            ->whereHas('permission', function ($q) {
                $q->where('module', 'reports');
            })
            ->first();

        if (!$permission || !$permission->can_view) {
            abort(403, 'Bạn không có quyền xem báo cáo');
        }

        // Mặc định là tháng hiện tại
        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->input('from_date'))
            : Carbon::now()->startOfMonth();

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->input('to_date'))
            : Carbon::now()->endOfMonth();

        if ($fromDate->gt($toDate)) {
            $temp = $fromDate;
            $fromDate = $toDate;
            $toDate = $temp;
        }

        $canFilterByDate = $permission->can_filter_by_date ?? true;
        $canPrint = $permission->can_print ?? true;

        // Lấy tỷ giá
        $exchangeRateInput = $request->input('exchange_rate');
        $exchangeRate = 1;
        if ($exchangeRateInput && $exchangeRateInput != '') {
            $cleanRate = str_replace(',', '', $exchangeRateInput);
            if (strpos($cleanRate, '.') !== false) {
                $parts = explode('.', $cleanRate);
                if (count($parts) == 2 && strlen($parts[1]) == 3) {
                    $cleanRate = $parts[0] . $parts[1];
                }
            }
            $exchangeRate = (float) $cleanRate;
            if ($exchangeRate > 0 && $exchangeRate < 1000) {
                $exchangeRate = $exchangeRate * 1000;
            }
        }

        // Query paintings nhập trong khoảng thời gian
        $paintings = Painting::whereBetween('import_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
            ->orderBy('import_date')
            ->orderBy('id')
            ->get();

        $reportData = [];
        $totalQuantity = 0;
        $totalPriceUsd = 0;
        $totalPriceVnd = 0;

        foreach ($paintings as $painting) {
            $reportData[] = [
                'import_date' => $painting->import_date ? $painting->import_date->format('d/m/Y') : '',
                'code' => $painting->code,
                'name' => $painting->name,
                'artist' => $painting->artist ?? '',
                'material' => $painting->material ?? '',
                'dimensions' => $this->formatDimensions($painting),
                'quantity' => $painting->quantity ?? 1,
                'price_usd' => $painting->price_usd ?? 0,
                'price_vnd' => $painting->price_vnd ?? 0,
                'status' => $this->getStatusText($painting->status),
            ];

            $totalQuantity += ($painting->quantity ?? 1);
            $totalPriceUsd += ($painting->price_usd ?? 0);
            $totalPriceVnd += ($painting->price_vnd ?? 0);
        }

        // Tính tổng quy đổi VND
        if ($exchangeRate > 1) {
            $grandTotalVnd = ($totalPriceUsd * $exchangeRate) + $totalPriceVnd;
        } else {
            $grandTotalVnd = $totalPriceVnd;
        }

        return view('reports.stock-import', compact(
            'reportData',
            'fromDate',
            'toDate',
            'exchangeRate',
            'canFilterByDate',
            'canPrint',
            'totalQuantity',
            'totalPriceUsd',
            'totalPriceVnd',
            'grandTotalVnd'
        ));
    }

    /**
     * Format dimensions for painting
     */
    private function formatDimensions($painting)
    {
        $parts = [];
        if ($painting->width)
            $parts[] = $painting->width;
        if ($painting->height)
            $parts[] = $painting->height;
        if ($painting->depth)
            $parts[] = $painting->depth;

        return count($parts) > 0 ? implode(' x ', $parts) . ' cm' : '';
    }

    /**
     * Get status text
     */
    private function getStatusText($status)
    {
        return match ($status) {
            'in_stock' => 'Còn hàng',
            'sold' => 'Đã bán',
            'reserved' => 'Đã đặt',
            default => $status,
        };
    }
    /**
     * Export Daily Cash Collection Report to Excel
     */
    public function exportDailyCashCollectionExcel(Request $request)
    {
        // Get report data using the same logic as dailyCashCollection
        $data = $this->getDailyCashCollectionData($request);

        $metadata = [
            'fromDate' => $data['fromDate']->format('d/m/Y'),
            'toDate' => $data['toDate']->format('d/m/Y'),
            'showroom' => $data['selectedShowroom'] ? $data['selectedShowroom']->name : 'All',
        ];

        $totals = [
            'totalAdjustmentUsd' => $data['totalAdjustmentUsd'],
            'totalAdjustmentVnd' => $data['totalAdjustmentVnd'],
            'totalCollectionUsd' => $data['totalCollectionUsd'],
            'totalCollectionVnd' => $data['totalCollectionVnd'],
            'totalCollectionAdjustmentUsd' => $data['totalCollectionAdjustmentUsd'],
            'totalCollectionAdjustmentVnd' => $data['totalCollectionAdjustmentVnd'],
            'cashCollectionVnd' => $data['cashCollectionVnd'],
            'cardCollectionVnd' => $data['cardCollectionVnd'],
        ];

        $filename = 'daily_cash_collection_' . $data['fromDate']->format('Ymd') . '_' . $data['toDate']->format('Ymd') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DailyCashCollectionExport($data['reportData'], $totals, $metadata),
            $filename
        );
    }

    /**
     * Export Daily Cash Collection Report to PDF
     */
    public function exportDailyCashCollectionPdf(Request $request)
    {
        $data = $this->getDailyCashCollectionData($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf.daily-cash-collection', $data);

        $filename = 'daily_cash_collection_' . $data['fromDate']->format('Ymd') . '_' . $data['toDate']->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Monthly Sales Report to Excel
     */
    public function exportMonthlySalesExcel(Request $request)
    {
        $data = $this->getMonthlySalesData($request);

        $metadata = [
            'fromDate' => $data['fromDate']->format('d/m/Y'),
            'toDate' => $data['toDate']->format('d/m/Y'),
            'showroom' => $data['selectedShowroom'] ? $data['selectedShowroom']->name : 'All',
        ];

        $totals = [
            'totalUsd' => $data['totalUsd'],
            'totalVnd' => $data['totalVnd'],
            'totalPaidVnd' => $data['totalPaidVnd'],
            'totalDebtVnd' => $data['totalDebtVnd'],
            'totalItems' => $data['totalItems'],
            'grandTotalVnd' => $data['grandTotalVnd'],
            'grandPaidVnd' => $data['grandPaidVnd'],
            'grandDebtVnd' => $data['grandDebtVnd'],
        ];

        $filename = 'monthly_sales_' . $data['fromDate']->format('Ymd') . '_' . $data['toDate']->format('Ymd') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MonthlySalesExport($data['reportData'], $totals, $metadata),
            $filename
        );
    }

    /**
     * Export Monthly Sales Report to PDF
     */
    public function exportMonthlySalesPdf(Request $request)
    {
        $data = $this->getMonthlySalesData($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf.monthly-sales', $data);

        $filename = 'monthly_sales_' . $data['fromDate']->format('Ymd') . '_' . $data['toDate']->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Debt Report to Excel
     */
    public function exportDebtReportExcel(Request $request)
    {
        $data = $this->getDebtReportData($request);

        $metadata = [
            'reportType' => $data['reportType'] === 'cumulative' ? 'Cumulative' : 'Period',
            'fromDate' => $data['fromDate']->format('d/m/Y'),
            'toDate' => $data['toDate']->format('d/m/Y'),
            'showroom' => $data['selectedShowroom'] ? $data['selectedShowroom']->name : 'All',
        ];

        $totals = [
            'totalSaleUsd' => $data['totalSaleUsd'],
            'totalSaleVnd' => $data['totalSaleVnd'],
            'totalPaidUsd' => $data['totalPaidUsd'],
            'totalPaidVnd' => $data['totalPaidVnd'],
            'totalDebtUsd' => $data['totalDebtUsd'],
            'totalDebtVnd' => $data['totalDebtVnd'],
            'grandTotalVnd' => $data['grandTotalVnd'],
            'grandPaidVnd' => $data['grandPaidVnd'],
            'grandDebtVnd' => $data['grandDebtVnd'],
        ];

        $filename = 'debt_report_' . $data['fromDate']->format('Ymd') . '_' . $data['toDate']->format('Ymd') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DebtReportExport($data['reportData'], $totals, $metadata),
            $filename
        );
    }

    /**
     * Export Debt Report to PDF
     */
    public function exportDebtReportPdf(Request $request)
    {
        $data = $this->getDebtReportData($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf.debt-report', $data);

        $filename = 'debt_report_' . $data['fromDate']->format('Ymd') . '_' . $data['toDate']->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Stock Import Report to Excel
     */
    public function exportStockImportExcel(Request $request)
    {
        $data = $this->getStockImportData($request);

        $metadata = [
            'fromDate' => $data['fromDate']->format('d/m/Y'),
            'toDate' => $data['toDate']->format('d/m/Y'),
        ];

        $totals = [
            'totalQuantity' => $data['totalQuantity'],
            'totalPriceUsd' => $data['totalPriceUsd'],
            'totalPriceVnd' => $data['totalPriceVnd'],
            'grandTotalVnd' => $data['grandTotalVnd'],
        ];

        $filename = 'stock_import_' . $data['fromDate']->format('Ymd') . '_' . $data['toDate']->format('Ymd') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StockImportExport($data['reportData'], $totals, $metadata),
            $filename
        );
    }

    /**
     * Export Stock Import Report to PDF
     */
    public function exportStockImportPdf(Request $request)
    {
        $data = $this->getStockImportData($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf.stock-import', $data);

        $filename = 'stock_import_' . $data['fromDate']->format('Ymd') . '_' . $data['toDate']->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Helper method to get daily cash collection data
     */
    private function getDailyCashCollectionData(Request $request)
    {
        // Duplicate the logic from dailyCashCollection method
        $user = Auth::user();
        $permission = $user->role?->rolePermissions()
            ->whereHas('permission', function ($q) {
                $q->where('module', 'reports');
            })
            ->first();

        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->input('from_date'))
            : Carbon::today();

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->input('to_date'))
            : Carbon::today();

        if ($fromDate->gt($toDate)) {
            $temp = $fromDate;
            $fromDate = $toDate;
            $toDate = $temp;
        }

        $showroomId = $request->input('showroom_id');
        $employeeId = $request->input('employee_id');
        $customerId = $request->input('customer_id');

        $dataScope = $permission->data_scope ?? 'all';
        $allowedShowrooms = $permission->allowed_showrooms;

        $canFilterByUser = $permission->can_filter_by_user ?? true;
        if (!$canFilterByUser) {
            $employeeId = null;
        }

        $selectedShowroom = null;
        if ($showroomId) {
            $selectedShowroom = Showroom::find($showroomId);
        }

        $exchangeRateInput = $request->input('exchange_rate');
        if (!$exchangeRateInput || $exchangeRateInput == '') {
            $exchangeRate = 1;
        } else {
            $cleanRate = str_replace(',', '', $exchangeRateInput);
            if (strpos($cleanRate, '.') !== false) {
                $parts = explode('.', $cleanRate);
                if (count($parts) == 2 && strlen($parts[1]) == 3) {
                    $cleanRate = $parts[0] . $parts[1];
                }
            }
            $exchangeRate = (float) $cleanRate;
            if ($exchangeRate > 0 && $exchangeRate < 1000) {
                $exchangeRate = $exchangeRate * 1000;
            }
        }

        $fromDateTime = $fromDate->format('Y-m-d') . ' 00:00:00';
        $toDateTime = $toDate->format('Y-m-d') . ' 23:59:59';

        $paymentsQuery = Payment::with(['sale.customer', 'sale.showroom', 'sale.items.painting', 'sale.items.supply', 'sale.items.frame', 'sale.user'])
            ->whereBetween('payment_date', [$fromDateTime, $toDateTime])
            ->where('transaction_type', 'sale_payment')
            ->whereHas('sale', function ($q) use ($showroomId, $employeeId, $customerId, $dataScope, $allowedShowrooms, $user) {
                if ($showroomId) {
                    $q->where('showroom_id', $showroomId);
                } elseif ($dataScope === 'showroom' && $allowedShowrooms) {
                    $q->whereIn('showroom_id', $allowedShowrooms);
                }

                if ($employeeId) {
                    $q->where('user_id', $employeeId);
                } elseif ($dataScope === 'own') {
                    $q->where('user_id', $user->id);
                }

                if ($customerId) {
                    $q->where('customer_id', $customerId);
                }
            });

        $paymentType = $request->input('payment_type');
        if ($paymentType === 'cash') {
            $paymentsQuery->where('payment_method', 'cash');
        } elseif ($paymentType === 'card_transfer') {
            $paymentsQuery->where('payment_method', '!=', 'cash');
        }

        $paymentsQuery = $paymentsQuery->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $reportData = [];
        $totalDepositUsd = 0;
        $totalDepositVnd = 0;
        $totalAdjustmentUsd = 0;
        $totalAdjustmentVnd = 0;
        $totalCollectionUsd = 0;
        $totalCollectionVnd = 0;
        $cashCollectionVnd = 0;
        $cardCollectionVnd = 0;

        foreach ($paymentsQuery as $payment) {
            $sale = $payment->sale;
            $firstItem = $sale->items->first();

            if (!$firstItem) {
                continue;
            }

            $idCode = '';
            if ($firstItem->painting_id) {
                $idCode = $firstItem->painting->code ?? 'N/A';
            } elseif ($firstItem->supply_id) {
                $idCode = $firstItem->supply->code ?? 'SUP' . $firstItem->supply_id;
            } elseif ($firstItem->frame_id) {
                $idCode = 'FRAME' . $firstItem->frame_id;
            }

            $saleDepositUsd = 0;
            $saleDepositVnd = 0;
            $saleAdjustmentUsd = 0;
            $saleAdjustmentVnd = 0;

            foreach ($sale->items as $item) {
                if ($item->is_returned)
                    continue;

                $subtotal = $item->quantity * ($item->currency == 'USD' ? $item->price_usd : $item->price_vnd);

                if ($item->currency == 'USD') {
                    $saleDepositUsd += $subtotal;
                    if ($item->discount_percent > 0) {
                        $discount = $subtotal * ($item->discount_percent / 100);
                        $saleAdjustmentUsd += -$discount;
                    }
                } else {
                    $saleDepositVnd += $subtotal;
                    if ($item->discount_percent > 0) {
                        $discount = $subtotal * ($item->discount_percent / 100);
                        $saleAdjustmentVnd += -$discount;
                    }
                }
            }

            $rowData = [
                'invoice_code' => $sale->invoice_code,
                'id_code' => $idCode,
                'customer_name' => $sale->customer->name,
                'deposit_usd' => $saleDepositUsd,
                'deposit_vnd' => $saleDepositVnd,
                'adjustment_usd' => $saleAdjustmentUsd,
                'adjustment_vnd' => $saleAdjustmentVnd,
                'collection_usd' => $payment->payment_usd ?? 0,
                'collection_vnd' => $payment->payment_vnd ?? 0,
                'collection_adjustment_usd' => 0,
                'collection_adjustment_vnd' => 0,
            ];

            $reportData[] = $rowData;

            $totalDepositUsd += $saleDepositUsd;
            $totalDepositVnd += $saleDepositVnd;
            $totalAdjustmentUsd += $saleAdjustmentUsd;
            $totalAdjustmentVnd += $saleAdjustmentVnd;
            $totalCollectionUsd += ($payment->payment_usd ?? 0);
            $totalCollectionVnd += ($payment->payment_vnd ?? 0);

            $paymentUsd = $payment->payment_usd ?? 0;
            $paymentVnd = $payment->payment_vnd ?? 0;
            $collectionVndForCashCard = ($paymentUsd * $exchangeRate) + $paymentVnd;

            if ($payment->payment_method == 'cash') {
                $cashCollectionVnd += $collectionVndForCashCard;
            } else {
                $cardCollectionVnd += $collectionVndForCashCard;
            }
        }

        $totalCollectionAdjustmentUsd = 0;
        $totalCollectionAdjustmentVnd = 0;

        return [
            'reportData' => $reportData,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'exchangeRate' => $exchangeRate,
            'selectedShowroom' => $selectedShowroom,
            'totalAdjustmentUsd' => $totalAdjustmentUsd,
            'totalAdjustmentVnd' => $totalAdjustmentVnd,
            'totalCollectionUsd' => $totalCollectionUsd,
            'totalCollectionVnd' => $totalCollectionVnd,
            'totalCollectionAdjustmentUsd' => $totalCollectionAdjustmentUsd,
            'totalCollectionAdjustmentVnd' => $totalCollectionAdjustmentVnd,
            'cashCollectionVnd' => $cashCollectionVnd,
            'cardCollectionVnd' => $cardCollectionVnd,
        ];
    }

    /**
     * Helper method to get monthly sales data
     */
    private function getMonthlySalesData(Request $request)
    {
        $user = Auth::user();
        $permission = $user->role?->rolePermissions()
            ->whereHas('permission', function ($q) {
                $q->where('module', 'reports');
            })
            ->first();

        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->input('from_date'))
            : Carbon::now()->startOfMonth();

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->input('to_date'))
            : Carbon::now()->endOfMonth();

        if ($fromDate->gt($toDate)) {
            $temp = $fromDate;
            $fromDate = $toDate;
            $toDate = $temp;
        }

        $showroomId = $request->input('showroom_id');
        $employeeId = $request->input('employee_id');

        $dataScope = $permission->data_scope ?? 'all';
        $allowedShowrooms = $permission->allowed_showrooms;

        $selectedShowroom = $showroomId ? Showroom::find($showroomId) : null;

        $exchangeRateInput = $request->input('exchange_rate');
        $exchangeRate = 1;
        if ($exchangeRateInput && $exchangeRateInput != '') {
            $cleanRate = str_replace(',', '', $exchangeRateInput);
            if (strpos($cleanRate, '.') !== false) {
                $parts = explode('.', $cleanRate);
                if (count($parts) == 2 && strlen($parts[1]) == 3) {
                    $cleanRate = $parts[0] . $parts[1];
                }
            }
            $exchangeRate = (float) $cleanRate;
            if ($exchangeRate > 0 && $exchangeRate < 1000) {
                $exchangeRate = $exchangeRate * 1000;
            }
        }

        $selectedYear = session('selected_year', date('Y'));

        $salesQuery = Sale::with(['customer', 'showroom', 'user', 'items.painting', 'items.supply', 'items.frame'])
            ->where('sale_status', 'completed')
            ->where('year', $selectedYear)
            ->whereBetween('sale_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')]);

        if ($showroomId) {
            $salesQuery->where('showroom_id', $showroomId);
        } elseif ($dataScope === 'showroom' && $allowedShowrooms) {
            $salesQuery->whereIn('showroom_id', $allowedShowrooms);
        }

        if ($employeeId) {
            $salesQuery->where('user_id', $employeeId);
        } elseif ($dataScope === 'own') {
            $salesQuery->where('user_id', $user->id);
        }

        $sales = $salesQuery->orderBy('sale_date')->orderBy('id')->get();

        $reportData = [];
        $totalUsd = 0;
        $totalVnd = 0;
        $totalPaidVnd = 0;
        $totalDebtVnd = 0;
        $totalItems = 0;

        foreach ($sales as $sale) {
            $firstItem = $sale->items->first();
            $idCode = '';
            $itemDescription = '';

            if ($firstItem) {
                if ($firstItem->painting_id && $firstItem->painting) {
                    $idCode = $firstItem->painting->code ?? '';
                    $itemDescription = $firstItem->painting->name ?? $firstItem->description;
                } elseif ($firstItem->supply_id && $firstItem->supply) {
                    $idCode = $firstItem->supply->code ?? '';
                    $itemDescription = $firstItem->supply->name ?? $firstItem->description;
                } elseif ($firstItem->frame_id) {
                    $idCode = 'FRAME' . $firstItem->frame_id;
                    $itemDescription = $firstItem->description;
                } else {
                    $itemDescription = $firstItem->description;
                }
            }

            $itemCount = $sale->items->sum('quantity');

            $reportData[] = [
                'sale_date' => $sale->sale_date->format('d/m/Y'),
                'invoice_code' => $sale->invoice_code,
                'id_code' => $idCode,
                'customer_name' => $sale->customer->name ?? 'Khách lẻ',
                'item_description' => $itemDescription,
                'item_count' => $itemCount,
                'total_usd' => $sale->total_usd,
                'total_vnd' => $sale->total_vnd,
                'paid_vnd' => $sale->paid_amount ?? 0,
                'debt_vnd' => $sale->debt_amount ?? 0,
                'showroom' => $sale->showroom->name ?? '',
                'employee' => $sale->user->name ?? '',
            ];

            $totalUsd += $sale->total_usd;
            $totalVnd += $sale->total_vnd;
            $totalPaidVnd += ($sale->paid_amount ?? 0);
            $totalDebtVnd += ($sale->debt_amount ?? 0);
            $totalItems += $itemCount;
        }

        if ($exchangeRate > 1) {
            $grandTotalVnd = ($totalUsd * $exchangeRate) + $totalVnd;
        } else {
            $grandTotalVnd = $totalVnd;
        }
        $grandPaidVnd = $totalPaidVnd;
        $grandDebtVnd = $totalDebtVnd;

        return [
            'reportData' => $reportData,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'exchangeRate' => $exchangeRate,
            'selectedShowroom' => $selectedShowroom,
            'totalUsd' => $totalUsd,
            'totalVnd' => $totalVnd,
            'totalPaidVnd' => $totalPaidVnd,
            'totalDebtVnd' => $totalDebtVnd,
            'totalItems' => $totalItems,
            'grandTotalVnd' => $grandTotalVnd,
            'grandPaidVnd' => $grandPaidVnd,
            'grandDebtVnd' => $grandDebtVnd,
        ];
    }

    /**
     * Helper method to get debt report data
     */
    private function getDebtReportData(Request $request)
    {
        $user = Auth::user();
        $permission = $user->role?->rolePermissions()
            ->whereHas('permission', function ($q) {
                $q->where('module', 'reports');
            })
            ->first();

        $reportType = $request->input('report_type', 'cumulative');

        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->input('from_date'))
            : Carbon::now()->startOfYear();

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->input('to_date'))
            : Carbon::now();

        if ($fromDate->gt($toDate)) {
            $temp = $fromDate;
            $fromDate = $toDate;
            $toDate = $temp;
        }

        $showroomId = $request->input('showroom_id');
        $customerId = $request->input('customer_id');

        $dataScope = $permission->data_scope ?? 'all';
        $allowedShowrooms = $permission->allowed_showrooms;

        $selectedShowroom = $showroomId ? Showroom::find($showroomId) : null;

        $exchangeRateInput = $request->input('exchange_rate');
        $exchangeRate = 1;
        if ($exchangeRateInput && $exchangeRateInput != '') {
            $cleanRate = str_replace(',', '', $exchangeRateInput);
            if (strpos($cleanRate, '.') !== false) {
                $parts = explode('.', $cleanRate);
                if (count($parts) == 2 && strlen($parts[1]) == 3) {
                    $cleanRate = $parts[0] . $parts[1];
                }
            }
            $exchangeRate = (float) $cleanRate;
            if ($exchangeRate > 0 && $exchangeRate < 1000) {
                $exchangeRate = $exchangeRate * 1000;
            }
        }

        $selectedYear = session('selected_year', date('Y'));

        $salesQuery = Sale::with(['customer', 'showroom', 'user', 'items.painting', 'payments'])
            ->where('sale_status', 'completed')
            ->where('year', $selectedYear)
            ->where(function ($q) {
                $q->where('payment_status', '!=', 'paid')
                    ->orWhere('total_usd', '>', 0);
            });

        if ($reportType === 'cumulative') {
            $salesQuery->whereDate('sale_date', '<=', $toDate->format('Y-m-d'));
        } else {
            $salesQuery->whereBetween('sale_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')]);
        }

        if ($showroomId) {
            $salesQuery->where('showroom_id', $showroomId);
        } elseif ($dataScope === 'showroom' && $allowedShowrooms) {
            $salesQuery->whereIn('showroom_id', $allowedShowrooms);
        }

        if ($customerId) {
            $salesQuery->where('customer_id', $customerId);
        }

        if ($dataScope === 'own') {
            $salesQuery->where('user_id', $user->id);
        }

        $sales = $salesQuery->orderBy('sale_date')->orderBy('id')->get();

        $reportData = [];
        $totalSaleUsd = 0;
        $totalSaleVnd = 0;
        $totalPaidUsd = 0;
        $totalPaidVnd = 0;
        $totalDebtUsd = 0;
        $totalDebtVnd = 0;

        foreach ($sales as $sale) {
            $firstItem = $sale->items->first();
            $idCode = '';

            if ($firstItem && $firstItem->painting_id && $firstItem->painting) {
                $idCode = $firstItem->painting->code ?? '';
            }

            $isUsdOnly = $sale->total_usd > 0 && $sale->total_vnd <= 0;
            $isVndOnly = $sale->total_vnd > 0 && $sale->total_usd <= 0;
            $isMixed = $sale->total_usd > 0 && $sale->total_vnd > 0;

            $debtUsd = $sale->debt_usd ?? 0;
            $debtVnd = $sale->debt_vnd ?? 0;
            $paidUsd = $sale->paid_usd ?? 0;
            $paidVnd = $sale->paid_vnd ?? 0;

            if ($debtUsd > 0.01 || $debtVnd > 1) {
                $reportData[] = [
                    'sale_date' => $sale->sale_date->format('d/m/Y'),
                    'invoice_code' => $sale->invoice_code,
                    'id_code' => $idCode,
                    'customer_name' => $sale->customer->name ?? 'Khách lẻ',
                    'customer_phone' => $sale->customer->phone ?? '',
                    'total_usd' => $sale->total_usd,
                    'total_vnd' => $sale->total_vnd,
                    'paid_usd' => $paidUsd,
                    'paid_vnd' => $paidVnd,
                    'debt_usd' => $debtUsd,
                    'debt_vnd' => $debtVnd,
                    'showroom' => $sale->showroom->name ?? '',
                    'is_usd_only' => $isUsdOnly,
                    'is_vnd_only' => $isVndOnly,
                    'is_mixed' => $isMixed,
                ];

                $totalSaleUsd += $sale->total_usd;
                $totalSaleVnd += $sale->total_vnd;

                if ($isUsdOnly) {
                    $totalPaidUsd += $paidUsd;
                    $totalDebtUsd += $debtUsd;
                } elseif ($isVndOnly) {
                    $totalPaidVnd += $paidVnd;
                    $totalDebtVnd += $debtVnd;
                } else {
                    $totalPaidUsd += $paidUsd;
                    $totalPaidVnd += $paidVnd;
                    $totalDebtUsd += $debtUsd;
                    $totalDebtVnd += $debtVnd;
                }
            }
        }

        if ($exchangeRate > 1) {
            $grandTotalVnd = ($totalSaleUsd * $exchangeRate) + $totalSaleVnd;
            $grandPaidVnd = ($totalPaidUsd * $exchangeRate) + $totalPaidVnd;
            $grandDebtVnd = ($totalDebtUsd * $exchangeRate) + $totalDebtVnd;
        } else {
            $grandTotalVnd = $totalSaleVnd;
            $grandPaidVnd = $totalPaidVnd;
            $grandDebtVnd = $totalDebtVnd;
        }

        return [
            'reportData' => $reportData,
            'reportType' => $reportType,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'exchangeRate' => $exchangeRate,
            'selectedShowroom' => $selectedShowroom,
            'totalSaleUsd' => $totalSaleUsd,
            'totalSaleVnd' => $totalSaleVnd,
            'totalPaidUsd' => $totalPaidUsd,
            'totalPaidVnd' => $totalPaidVnd,
            'totalDebtUsd' => $totalDebtUsd,
            'totalDebtVnd' => $totalDebtVnd,
            'grandTotalVnd' => $grandTotalVnd,
            'grandPaidVnd' => $grandPaidVnd,
            'grandDebtVnd' => $grandDebtVnd,
        ];
    }

    /**
     * Helper method to get stock import data
     */
    private function getStockImportData(Request $request)
    {
        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->input('from_date'))
            : Carbon::now()->startOfMonth();

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->input('to_date'))
            : Carbon::now()->endOfMonth();

        if ($fromDate->gt($toDate)) {
            $temp = $fromDate;
            $fromDate = $toDate;
            $toDate = $temp;
        }

        $exchangeRateInput = $request->input('exchange_rate');
        $exchangeRate = 1;
        if ($exchangeRateInput && $exchangeRateInput != '') {
            $cleanRate = str_replace(',', '', $exchangeRateInput);
            if (strpos($cleanRate, '.') !== false) {
                $parts = explode('.', $cleanRate);
                if (count($parts) == 2 && strlen($parts[1]) == 3) {
                    $cleanRate = $parts[0] . $parts[1];
                }
            }
            $exchangeRate = (float) $cleanRate;
            if ($exchangeRate > 0 && $exchangeRate < 1000) {
                $exchangeRate = $exchangeRate * 1000;
            }
        }

        $paintings = Painting::whereBetween('import_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
            ->orderBy('import_date')
            ->orderBy('id')
            ->get();

        $reportData = [];
        $totalQuantity = 0;
        $totalPriceUsd = 0;
        $totalPriceVnd = 0;

        foreach ($paintings as $painting) {
            $reportData[] = [
                'import_date' => $painting->import_date ? $painting->import_date->format('d/m/Y') : '',
                'code' => $painting->code,
                'name' => $painting->name,
                'artist' => $painting->artist ?? '',
                'material' => $painting->material ?? '',
                'dimensions' => $this->formatDimensions($painting),
                'quantity' => $painting->quantity ?? 1,
                'price_usd' => $painting->price_usd ?? 0,
                'price_vnd' => $painting->price_vnd ?? 0,
                'status' => $this->getStatusText($painting->status),
            ];

            $totalQuantity += ($painting->quantity ?? 1);
            $totalPriceUsd += ($painting->price_usd ?? 0);
            $totalPriceVnd += ($painting->price_vnd ?? 0);
        }

        if ($exchangeRate > 1) {
            $grandTotalVnd = ($totalPriceUsd * $exchangeRate) + $totalPriceVnd;
        } else {
            $grandTotalVnd = $totalPriceVnd;
        }

        return [
            'reportData' => $reportData,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'totalQuantity' => $totalQuantity,
            'totalPriceUsd' => $totalPriceUsd,
            'totalPriceVnd' => $totalPriceVnd,
            'grandTotalVnd' => $grandTotalVnd,
        ];
    }
}
