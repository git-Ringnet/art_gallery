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
        $perPage = 15;
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

        // Statistics
        $stats = [
            'total_payments' => Payment::sum('amount'),
            'total_debt' => \App\Models\Sale::where('debt_amount', '>', 0)->sum('debt_amount'),
            'debt_count' => \App\Models\Sale::where('debt_amount', '>', 0)->count(),
            'total_count' => Payment::count(),
        ];

        return view('debts.index', compact('payments', 'stats'));
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

        // Tìm kiếm khách hàng có thanh toán
        $customers = \App\Models\Customer::whereHas('sales.payments')
            ->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'phone']);

        // Tìm kiếm mã hóa đơn có thanh toán
        $invoices = \App\Models\Sale::whereHas('payments')
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
        // Lấy TẤT CẢ Payment (lịch sử thanh toán)
        $query = Payment::with(['sale.customer', 'sale.debt', 'sale.payments']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('sale', function($q) use ($search) {
                $q->where('invoice_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by date
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        // Filter by amount range
        if ($request->filled('amount_from')) {
            $query->where('amount', '>=', $request->amount_from);
        }
        if ($request->filled('amount_to')) {
            $query->where('amount', '<=', $request->amount_to);
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
        
        // Lấy số nợ hiện tại từ Sale (chính xác nhất)
        $currentDebt = $debt->sale->debt_amount;
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $currentDebt,
            'payment_method' => 'nullable|string|in:cash,bank_transfer,card',
            'notes' => 'nullable|string'
        ], [
            'amount.max' => 'Số tiền thu không được vượt quá số nợ hiện tại: ' . number_format($currentDebt, 0, ',', '.') . 'đ',
            'amount.required' => 'Vui lòng nhập số tiền thu',
            'amount.min' => 'Số tiền thu phải lớn hơn 0',
        ]);

        // Tạo payment mới
        $payment = Payment::create([
            'sale_id' => $debt->sale_id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'] ?? 'cash',
            'transaction_type' => 'sale_payment',
            'payment_date' => now(),
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Update sale payment status (sẽ tự động update debt)
        $debt->sale->refresh(); // Refresh để lấy data mới nhất
        $debt->sale->updatePaymentStatus();

        return redirect()->route('debt.index')
            ->with('success', 'Thu nợ thành công! Số tiền: ' . number_format($payment->amount, 0, ',', '.') . 'đ');
    }
}
