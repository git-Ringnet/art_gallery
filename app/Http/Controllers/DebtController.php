<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');

        // Mock data - replace with actual database queries
        $debts = [
            [
                'customer_name' => 'Nguyễn Văn A',
                'customer_phone' => '0123 456 789',
                'invoice_id' => 'HD001',
                'total' => 2500000,
                'paid' => 2000000,
                'debt' => 500000,
                'created_at' => '15/12/2024',
                'status' => 'pending'
            ],
            [
                'customer_name' => 'Trần Thị B',
                'customer_phone' => '0987 654 321',
                'invoice_id' => 'HD002',
                'total' => 1800000,
                'paid' => 600000,
                'debt' => 1200000,
                'created_at' => '10/12/2024',
                'status' => 'overdue'
            ],
            [
                'customer_name' => 'Lê Văn C',
                'customer_phone' => '0369 258 147',
                'invoice_id' => 'HD003',
                'total' => 3200000,
                'paid' => 3200000,
                'debt' => 0,
                'created_at' => '08/12/2024',
                'status' => 'paid'
            ]
        ];

        // Apply filters
        if ($search) {
            $debts = array_filter($debts, function($debt) use ($search) {
                return stripos($debt['customer_name'], $search) !== false ||
                       stripos($debt['customer_phone'], $search) !== false ||
                       stripos($debt['invoice_id'], $search) !== false;
            });
        }

        if ($status) {
            $debts = array_filter($debts, function($debt) use ($status) {
                return $debt['status'] === $status;
            });
        }

        return view('debt.index', compact('debts'));
    }

    public function show($id)
    {
        // Mock data - replace with actual database query
        $debt = [
            'invoice_id' => $id,
            'customer_name' => 'Nguyễn Văn A',
            'customer_phone' => '0123 456 789',
            'customer_address' => '123 Đường ABC, Quận 1',
            'total' => 2500000,
            'paid' => 2000000,
            'debt' => 500000,
            'created_at' => '15/12/2024',
            'status' => 'pending',
            'payment_history' => [
                [
                    'date' => '15/12/2024',
                    'amount' => 2000000,
                    'method' => 'Tiền mặt',
                    'note' => 'Thanh toán lần 1'
                ]
            ]
        ];

        return view('debt.show', compact('debt'));
    }

    public function collect(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'note' => 'nullable|string'
        ]);

        // Process debt collection
        // This is where you'd update the database
        
        return redirect()->route('debt.index')
            ->with('success', 'Đã thu nợ thành công');
    }

    public function export(Request $request)
    {
        // Export debt report to Excel
        // Implementation depends on your export library (e.g., Laravel Excel)
        
        return response()->download(storage_path('app/debt_report.xlsx'));
    }
}
