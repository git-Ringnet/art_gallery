<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReturnsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Mock data - replace with actual database queries
        $returns = [
            [
                'invoice_id' => 'HD001',
                'return_date' => '16/12/2024',
                'customer_name' => 'Nguyễn Văn A',
                'customer_phone' => '0123 456 789',
                'products' => 'Tranh sơn dầu',
                'quantity' => 1,
                'refund_amount' => 2500000,
                'status' => 'completed'
            ],
            [
                'invoice_id' => 'HD002',
                'return_date' => '15/12/2024',
                'customer_name' => 'Trần Thị B',
                'customer_phone' => '0987 654 321',
                'products' => 'Khung 30x40',
                'quantity' => 2,
                'refund_amount' => 500000,
                'status' => 'pending'
            ]
        ];

        return view('returns.index', compact('returns'));
    }

    public function searchInvoice(Request $request)
    {
        $invoiceCode = $request->get('invoice_code');

        // Mock data - replace with actual database query
        $invoice = [
            'id' => $invoiceCode,
            'date' => '15/12/2024',
            'customer' => [
                'name' => 'Nguyễn Văn A',
                'phone' => '0123 456 789',
                'address' => '123 Đường ABC, Quận 1'
            ],
            'total' => 2500000,
            'products' => [
                [
                    'code' => 'T001',
                    'name' => 'Tranh sơn dầu phong cảnh',
                    'quantity' => 1,
                    'price' => 2500000,
                    'image' => 'https://via.placeholder.com/60x60/4F46E5/FFFFFF?text=T1'
                ]
            ]
        ];

        return response()->json($invoice);
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|string',
            'items' => 'required|array',
            'items.*.product_code' => 'required|string',
            'items.*.return_quantity' => 'required|numeric|min:1',
            'reason' => 'nullable|string'
        ]);

        // Process return
        // This is where you'd update the database
        
        return redirect()->route('returns.index')
            ->with('success', 'Đã xử lý trả hàng thành công');
    }
}
