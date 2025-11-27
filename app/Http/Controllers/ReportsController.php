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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    /**
     * Display daily cash collection report
     */
    public function dailyCashCollection(Request $request)
    {
        // Kiểm tra quyền truy cập module reports
        $user = Auth::user();
        $permission = $user->role?->rolePermissions()
            ->whereHas('permission', function($q) {
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
            // Nếu user chọn showroom không được phép, reset về null
            if ($showroomId && !in_array($showroomId, $allowedShowrooms)) {
                $showroomId = null;
            }
        } else {
            $showrooms = Showroom::orderBy('name')->get();
        }
        
        // Lấy danh sách nhân viên (chỉ sales staff)
        $employees = User::whereHas('role', function($q) {
            $q->where('name', 'Nhân viên bán hàng');
        })->orderBy('name')->get();
        
        // Lấy danh sách khách hàng
        $customers = Customer::orderBy('name')->get();
        
        // Lấy thông tin showroom nếu được chọn
        $selectedShowroom = null;
        if ($showroomId) {
            $selectedShowroom = Showroom::find($showroomId);
        }
        
        // Lấy tỷ giá từ request, mặc định là 1 (không chuyển đổi)
        $exchangeRate = $request->input('exchange_rate');
        if (!$exchangeRate || $exchangeRate == '') {
            $exchangeRate = 1; // Mặc định là 1 nếu không nhập
        }
        
        
        // Logic mới: Báo cáo dựa trên PAYMENTS trong khoảng thời gian
        // Mỗi dòng = 1 payment, hiển thị thông tin sale tương ứng
        
        // Fix: Thêm thời gian để lọc đúng cả ngày (00:00:00 - 23:59:59)
        $fromDateTime = $fromDate->format('Y-m-d') . ' 00:00:00';
        $toDateTime = $toDate->format('Y-m-d') . ' 23:59:59';
        
        $paymentsQuery = Payment::with(['sale.customer', 'sale.showroom', 'sale.items.painting', 'sale.items.supply', 'sale.items.frame', 'sale.user'])
            ->whereBetween('payment_date', [$fromDateTime, $toDateTime])
            ->where('transaction_type', 'sale_payment')
            ->whereHas('sale', function($q) use ($showroomId, $employeeId, $customerId, $dataScope, $allowedShowrooms, $user) {
                // Bỏ filter sale_status để hiển thị tất cả payments
                // Báo cáo thu tiền nên hiển thị TẤT CẢ tiền thu được, bất kể trạng thái sale
                
                // Filter theo showroom
                if ($showroomId) {
                    $q->where('showroom_id', $showroomId);
                } elseif ($dataScope === 'showroom' && $allowedShowrooms) {
                    $q->whereIn('showroom_id', $allowedShowrooms);
                }
                
                // Filter theo nhân viên
                if ($employeeId) {
                    $q->where('user_id', $employeeId);
                } elseif ($dataScope === 'own') {
                    // Chỉ xem dữ liệu của chính mình
                    $q->where('user_id', $user->id);
                }
                
                // Filter theo khách hàng
                if ($customerId) {
                    $q->where('customer_id', $customerId);
                }
            })
            ->orderBy('payment_date')
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
            
            // Lấy item đầu tiên của sale để hiển thị (hoặc có thể tách thành nhiều dòng nếu cần)
            $firstItem = $sale->items->first();
            
            if (!$firstItem) {
                continue; // Skip nếu không có item
            }
            
            // ID Code = mã tranh hoặc supply hoặc frame
            $idCode = '';
            if ($firstItem->painting_id) {
                $idCode = $firstItem->painting->code ?? 'N/A';
            } elseif ($firstItem->supply_id) {
                $idCode = $firstItem->supply->code ?? 'SUP' . $firstItem->supply_id;
            } elseif ($firstItem->frame_id) {
                $idCode = 'FRAME' . $firstItem->frame_id;
            }
            
            // Tính tổng deposit và adjustment của toàn bộ sale
            $saleDepositUsd = 0;
            $saleDepositVnd = 0;
            $saleAdjustmentUsd = 0;
            $saleAdjustmentVnd = 0;
            
            foreach ($sale->items as $item) {
                if ($item->is_returned) continue;
                
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
                'collection_usd' => $payment->payment_usd,
                'collection_vnd' => $payment->payment_usd > 0 ? 0 : $payment->amount, // Nếu trả USD thì VND = 0
                'collection_adjustment_usd' => 0, // Để sau này mở rộng
                'collection_adjustment_vnd' => 0,
            ];
            
            $reportData[] = $rowData;
            
            // Cộng vào tổng
            $totalDepositUsd += $saleDepositUsd;
            $totalDepositVnd += $saleDepositVnd;
            $totalAdjustmentUsd += $saleAdjustmentUsd;
            $totalAdjustmentVnd += $saleAdjustmentVnd;
            $totalCollectionUsd += $payment->payment_usd;
            $totalCollectionVnd += ($payment->payment_usd > 0 ? 0 : $payment->amount); // Nếu trả USD thì VND = 0
            
            // Phân loại cash/card
            // Tính VND cho cash/card: Nếu trả USD thì quy đổi, nếu trả VND thì lấy trực tiếp
            $collectionVndForCashCard = $payment->payment_usd > 0 
                ? ($payment->payment_usd * $exchangeRate) 
                : $payment->amount;
            
            // CASH: Chỉ payment_method = 'cash'
            // CREDIT CARD: Tất cả còn lại (card, bank_transfer, ...)
            if ($payment->payment_method == 'cash') {
                $cashCollectionVnd += $collectionVndForCashCard;
            } else {
                // card, bank_transfer, hoặc bất kỳ phương thức nào khác → Credit Card
                $cardCollectionVnd += $collectionVndForCashCard;
            }
        }
        
        // Tính Total VND cho Deposit và Adjustment
        // Chỉ quy đổi USD sang VND nếu có nhập tỷ giá (khác 1)
        if ($exchangeRate > 1) {
            $totalDepositTotalVnd = ($totalDepositUsd * $exchangeRate) + $totalDepositVnd;
            $totalAdjustmentTotalVnd = ($totalAdjustmentUsd * $exchangeRate) + $totalAdjustmentVnd;
            $grandTotalVnd = ($totalCollectionUsd * $exchangeRate) + $totalCollectionVnd;
        } else {
            // Không nhập tỷ giá → Chỉ tính VND, bỏ qua USD
            $totalDepositTotalVnd = $totalDepositVnd;
            $totalAdjustmentTotalVnd = $totalAdjustmentVnd;
            $grandTotalVnd = $totalCollectionVnd;
        }
        
        // Placeholder cho Collection Adjustment totals
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
     * Display reports index/dashboard
     */
    public function index()
    {
        return view('reports.daily-cash-collection');
    }
}
