<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $allPayments = $this->getFilteredPayments($request);

        // Manual pagination
        $perPage = 10;
        $currentPage = request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        
        $paginatedPayments = $allPayments->slice($offset, $perPage)->values();
        
        $payments = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedPayments,
            $allPayments->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Lọc theo năm đang chọn
        $selectedYear = session('selected_year', date('Y'));
        
        // Statistics - CHỈ tính các phiếu đã duyệt hoặc đã hủy (trả hết)
        $stats = [
            'total_payments' => Payment::whereHas('sale', function($q) use ($selectedYear) {
                $q->whereIn('sale_status', ['completed', 'cancelled'])
                  ->where('year', $selectedYear);
            })->sum('amount'),
            'total_debt' => \App\Models\Sale::where('sale_status', 'completed')
                ->where('year', $selectedYear)
                ->where('debt_amount', '>', 0)
                ->sum('debt_amount'),
            'debt_count' => \App\Models\Sale::where('sale_status', 'completed')
                ->where('year', $selectedYear)
                ->where('debt_amount', '>', 0)
                ->count(),
            'total_count' => Payment::whereHas('sale', function($q) use ($selectedYear) {
                $q->whereIn('sale_status', ['completed', 'cancelled'])
                  ->where('year', $selectedYear);
            })->count(),
        ];

        // Truyền quyền vào view
        $canSearch = \App\Helpers\PermissionHelper::canSearch('debt');
        $canFilterByDate = \Illuminate\Support\Facades\Auth::user()->email === 'admin@example.com' || 
                          (\Illuminate\Support\Facades\Auth::user()->role && \Illuminate\Support\Facades\Auth::user()->role->getModulePermissions('debt') && 
                           \Illuminate\Support\Facades\Auth::user()->role->getModulePermissions('debt')->can_filter_by_date);
        $canFilterByStatus = \Illuminate\Support\Facades\Auth::user()->email === 'admin@example.com' || 
                            (\Illuminate\Support\Facades\Auth::user()->role && \Illuminate\Support\Facades\Auth::user()->role->getModulePermissions('debt') && 
                             \Illuminate\Support\Facades\Auth::user()->role->getModulePermissions('debt')->can_filter_by_status);
        $canFilterByShowroom = \Illuminate\Support\Facades\Auth::user()->email === 'admin@example.com' || 
                              (\Illuminate\Support\Facades\Auth::user()->role && \Illuminate\Support\Facades\Auth::user()->role->getModulePermissions('debt') && 
                               \Illuminate\Support\Facades\Auth::user()->role->getModulePermissions('debt')->can_filter_by_showroom);
        
        // Lấy danh sách showrooms
        $showrooms = \App\Models\Showroom::orderBy('name')->get();

        return view('debts.index', compact('payments', 'stats', 'canSearch', 'canFilterByDate', 'canFilterByStatus', 'canFilterByShowroom', 'showrooms'));
    }

    public function show($id)
    {
        $debt = Debt::with([
            'customer', 
            'sale.saleItems', 
            'sale.payments' => function($query) {
                $query->orderBy('payment_date', 'desc')->orderBy('id', 'desc');
            }
        ])->findOrFail($id);
        return view('debts.show', compact('debt'));
    }

    public function searchSuggestions(Request $request)
    {
        $search = $request->get('q', '');
        
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        // Tìm kiếm khách hàng có thanh toán (chỉ phiếu đã duyệt hoặc đã hủy)
        $customers = \App\Models\Customer::whereHas('sales', function($q) {
                $q->whereIn('sale_status', ['completed', 'cancelled'])->whereHas('payments');
            })
            ->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'phone']);

        // Tìm kiếm mã hóa đơn có thanh toán (chỉ phiếu đã duyệt hoặc đã hủy, theo năm đang chọn)
        $invoices = \App\Models\Sale::whereIn('sale_status', ['completed', 'cancelled'])
            ->where('year', $selectedYear)
            ->whereHas('payments')
            ->where('invoice_code', 'like', "%{$search}%")
            ->limit(5)
            ->get(['id', 'invoice_code']);

        $suggestions = [];
        
        foreach ($customers as $customer) {
            $suggestions[] = [
                'type' => 'customer',
                'label' => $customer->name . ($customer->phone ? ' - ' . $customer->phone : ''),
                'value' => $customer->name
            ];
        }
        
        foreach ($invoices as $invoice) {
            $suggestions[] = [
                'type' => 'invoice',
                'label' => 'Mã HĐ: ' . $invoice->invoice_code,
                'value' => $invoice->invoice_code
            ];
        }

        return response()->json($suggestions);
    }

    private function getFilteredPayments(Request $request, $all = false)
    {
        // Lọc theo năm đang chọn
        $selectedYear = session('selected_year', date('Y'));
        
        // Lấy TẤT CẢ Payment (lịch sử thanh toán) - CHỈ của các phiếu đã duyệt hoặc đã hủy (trả hết)
        $query = Payment::with(['sale.customer', 'sale.debt', 'sale.payments', 'createdBy'])
            ->whereHas('sale', function($q) use ($selectedYear) {
                $q->whereIn('sale_status', ['completed', 'cancelled'])
                  ->where('year', $selectedYear);
            });
        
        // Áp dụng phạm vi dữ liệu - custom logic cho Payment
        \Log::info('Debt Filter - Start', [
            'is_authenticated' => \Illuminate\Support\Facades\Auth::check(),
            'user_email' => \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->email : null,
            'is_admin' => \Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email === 'admin@example.com',
        ]);
        
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email !== 'admin@example.com') {
            $role = \Illuminate\Support\Facades\Auth::user()->role;
            \Log::info('Debt Filter - Non-admin user', [
                'has_role' => $role !== null,
                'role_name' => $role ? $role->name : null,
            ]);
            
            if ($role) {
                $dataScope = $role->getDataScope('debt');
                
                // Debug log
                \Log::info('Debt Data Scope Filter', [
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'user_email' => \Illuminate\Support\Facades\Auth::user()->email,
                    'role_name' => $role->name,
                    'data_scope' => $dataScope,
                ]);
                
                switch ($dataScope) {
                    case 'own':
                        // Chỉ xem payment mà chính mình thu (created_by)
                        $query->where('created_by', \Illuminate\Support\Facades\Auth::id());
                        \Log::info('Filtering by created_by', ['created_by' => \Illuminate\Support\Facades\Auth::id()]);
                        break;
                    
                    case 'showroom':
                        // Xem payment của các sale thuộc showroom được phép
                        $allowedShowrooms = $role->getAllowedShowrooms('debt');
                        if ($allowedShowrooms && is_array($allowedShowrooms) && count($allowedShowrooms) > 0) {
                            $query->whereHas('sale', function($q) use ($allowedShowrooms) {
                                $q->whereIn('showroom_id', $allowedShowrooms);
                            });
                        }
                        break;
                    
                    case 'all':
                        // Xem tất cả - không filter
                        break;
                    
                    case 'none':
                    default:
                        // Không có quyền
                        $query->whereRaw('1 = 0');
                        break;
                }
            }
        }

        // Search (nếu có quyền)
        if ($request->filled('search') && \App\Helpers\PermissionHelper::canSearch('debt')) {
            $search = $request->search;
            $query->whereHas('sale', function($q) use ($search) {
                $q->whereIn('sale_status', ['completed', 'cancelled'])
                  ->where(function($sq) use ($search) {
                      $sq->where('invoice_code', 'like', "%{$search}%")
                         ->orWhereHas('customer', function($cq) use ($search) {
                             $cq->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                         });
                  });
            });
        }

        // Filter by date (nếu có quyền)
        $canFilterDate = \Illuminate\Support\Facades\Auth::user()->email === 'admin@example.com' || 
                        (\Illuminate\Support\Facades\Auth::user()->role && \Illuminate\Support\Facades\Auth::user()->role->getModulePermissions('debt') && 
                         \Illuminate\Support\Facades\Auth::user()->role->getModulePermissions('debt')->can_filter_by_date);
        if ($canFilterDate) {
            if ($request->filled('date_from')) {
                $query->whereDate('payment_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('payment_date', '<=', $request->date_to);
            }
        }

        // Filter by amount range
        if ($request->filled('amount_from')) {
            $query->where('amount', '>=', $request->amount_from);
        }
        if ($request->filled('amount_to')) {
            $query->where('amount', '<=', $request->amount_to);
        }
        
        // Filter by showroom (nếu có quyền)
        $canFilterShowroom = \Illuminate\Support\Facades\Auth::user()->email === 'admin@example.com' || 
                            (\Illuminate\Support\Facades\Auth::user()->role && \Illuminate\Support\Facades\Auth::user()->role->getModulePermissions('debt') && 
                             \Illuminate\Support\Facades\Auth::user()->role->getModulePermissions('debt')->can_filter_by_showroom);
        if ($canFilterShowroom && $request->filled('showroom_id')) {
            $query->whereHas('sale', function($q) use ($request) {
                $q->where('showroom_id', $request->showroom_id);
            });
        }

        // Get all payments first
        $allPayments = $query->orderBy('id', 'desc')->get();

        // Filter by payment status - tính trạng thái TẠI THỜI ĐIỂM thanh toán
        if ($request->filled('payment_status')) {
            $statusFilter = $request->payment_status;
            
            $allPayments = $allPayments->filter(function($payment) use ($statusFilter) {
                // Tính tổng đã trả TẠI THỜI ĐIỂM payment này
                $paidAtThisTime = $payment->sale->payments()
                    ->where('id', '<=', $payment->id)
                    ->sum('amount');
                $totalAmount = $payment->sale->total_vnd;
                
                // Xác định trạng thái tại thời điểm đó
                if ($paidAtThisTime >= $totalAmount) {
                    $status = 'paid';
                } elseif ($paidAtThisTime > 0) {
                    $status = 'partial';
                } else {
                    $status = 'unpaid';
                }
                
                return $status === $statusFilter;
            });
        }

        return $allPayments;
    }

    public function exportExcel(Request $request)
    {
        $scope = $request->get('scope', 'current');
        
        if ($scope === 'all') {
            $payments = $this->getFilteredPayments($request, true);
        } else {
            // Current page only
            $allPayments = $this->getFilteredPayments($request);
            $perPage = 15;
            $currentPage = $request->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            $payments = $allPayments->slice($offset, $perPage)->values();
        }

        $filename = 'lich-su-cong-no-' . date('Y-m-d-His') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DebtHistoryExport($payments),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $scope = $request->get('scope', 'current');
        
        if ($scope === 'all') {
            $payments = $this->getFilteredPayments($request, true);
        } else {
            // Current page only
            $allPayments = $this->getFilteredPayments($request);
            $perPage = 15;
            $currentPage = $request->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            $payments = $allPayments->slice($offset, $perPage)->values();
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('debts.pdf', compact('payments'));
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('lich-su-cong-no-' . date('Y-m-d-His') . '.pdf');
    }

    public function collect(Request $request, $id)
    {
        $debt = Debt::with('sale')->findOrFail($id);
        
        // Lấy số nợ hiện tại từ Sale
        $currentDebtVnd = $debt->sale->debt_amount;
        $currentDebtUsd = $debt->sale->debt_usd;
        
        // Validate input cơ bản
        $validated = $request->validate([
            'payment_usd' => 'nullable|numeric|min:0',
            'payment_vnd' => 'nullable|numeric|min:0',
            'current_exchange_rate' => 'nullable|numeric|min:1',
            'payment_method' => 'required|string|in:cash,bank_transfer,card',
            'notes' => 'nullable|string'
        ]);

        // Lấy giá trị
        $paymentUsd = $validated['payment_usd'] ?? 0;
        $paymentVnd = $validated['payment_vnd'] ?? 0;
        $exchangeRate = $validated['current_exchange_rate'] ?? $debt->sale->exchange_rate;
        
        // Tính tổng tiền quy đổi
        $totalUsd = $paymentUsd + ($paymentVnd / $exchangeRate);
        $totalVnd = ($paymentUsd * $exchangeRate) + $paymentVnd;
        
        // Validate logic (cho phép sai số nhỏ)
        $usdTolerance = 0.01;
        $vndTolerance = 1000;
        
        if ($totalUsd > $currentDebtUsd + $usdTolerance) {
            // Nếu vượt quá nợ USD, kiểm tra xem có phải do trả đủ VND gốc không
            if ($totalVnd > $currentDebtVnd + $vndTolerance) {
                 return back()->withErrors(['amount' => 'Số tiền thu vượt quá số nợ hiện tại.']);
            }
        }
        
        if ($totalUsd <= 0) {
             return back()->withErrors(['amount' => 'Vui lòng nhập số tiền cần thu.']);
        }

        // Tạo payment mới
        $currentTime = now();
        
        $payment = Payment::create([
            'sale_id' => $debt->sale_id,
            'amount' => $totalVnd, // Lưu tổng VND quy đổi
            'payment_usd' => $paymentUsd,
            'payment_vnd' => $paymentVnd,
            'payment_exchange_rate' => $exchangeRate,
            'payment_method' => $validated['payment_method'],
            'transaction_type' => 'sale_payment',
            'payment_date' => $currentTime,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
        
        // Update sale payment status
        $debt->sale->refresh();
        $debt->sale->updatePaymentStatus();

        return redirect()->route('debt.index')
            ->with('success', 'Thu nợ thành công! Số tiền: ' . number_format($payment->amount, 0, ',', '.') . 'đ');
    }
}
