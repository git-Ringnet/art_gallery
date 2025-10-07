<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        // Mock data - replace with actual database queries
        $sales = [
            [
                'id' => 'HD001',
                'date' => '15/12/2024',
                'customer_name' => 'Nguyễn Văn A',
                'customer_phone' => '0123 456 789',
                'products' => 'Tranh sơn dầu + Khung 30x40',
                'total' => 2500000,
                'paid' => 2000000,
                'debt' => 500000,
                'status' => 'partial'
            ],
            [
                'id' => 'HD002',
                'date' => '14/12/2024',
                'customer_name' => 'Trần Thị B',
                'customer_phone' => '0987 654 321',
                'products' => 'Tranh canvas + Khung 40x60',
                'total' => 1800000,
                'paid' => 1800000,
                'debt' => 0,
                'status' => 'paid'
            ]
        ];

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $showrooms = $this->getShowrooms();
        $supplies = $this->getSupplies();
        
        return view('sales.create', compact('showrooms', 'supplies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'nullable|string',
            'showroom_id' => 'required|string',
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.currency' => 'required|in:USD,VND',
            'items.*.price' => 'required|numeric|min:0',
            'exchange_rate' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'payment_amount' => 'nullable|numeric|min:0'
        ]);

        // Process and save invoice
        // This is where you'd save to database
        
        // Generate invoice ID (in real app, this would come from database)
        $invoiceId = 'HD' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Check if user wants to print
        if ($request->input('action') === 'save_and_print') {
            return redirect()->route('sales.print', $invoiceId)
                ->with('success', 'Hóa đơn đã được tạo thành công');
        }
        
        return redirect()->route('sales.index')
            ->with('success', 'Hóa đơn đã được tạo thành công');
    }

    public function show($id)
    {
        // Mock data - replace with actual database query
        $sale = [
            'id' => $id,
            'date' => '15/12/2024',
            'customer_name' => 'Nguyễn Văn A',
            'customer_phone' => '0123 456 789',
            'customer_address' => '123 Đường ABC, Quận 1',
            'items' => [
                [
                    'name' => 'Tranh sơn dầu',
                    'quantity' => 1,
                    'price' => 2000000,
                    'total' => 2000000
                ],
                [
                    'name' => 'Khung 30x40',
                    'quantity' => 1,
                    'price' => 500000,
                    'total' => 500000
                ]
            ],
            'subtotal' => 2500000,
            'discount' => 0,
            'total' => 2500000,
            'paid' => 2000000,
            'debt' => 500000
        ];

        return view('sales.show', compact('sale'));
    }

    public function edit($id)
    {
        // Get sale data
        $sale = $this->show($id)->getData()['sale'];
        $showrooms = $this->getShowrooms();
        $supplies = $this->getSupplies();
        
        return view('sales.edit', compact('sale', 'showrooms', 'supplies'));
    }

    public function update(Request $request, $id)
    {
        // Validate and update
        
        return redirect()->route('sales.index')
            ->with('success', 'Hóa đơn đã được cập nhật');
    }

    public function destroy($id)
    {
        // Delete invoice
        
        return redirect()->route('sales.index')
            ->with('success', 'Hóa đơn đã được xóa');
    }

    public function print($id)
    {
        $sale = $this->show($id)->getData()['sale'];
        
        return view('sales.print', compact('sale'));
    }

    private function getShowrooms()
    {
        return [
            ['id' => 'SR01', 'name' => 'Showroom Trung tâm'],
            ['id' => 'SR02', 'name' => 'Showroom Quận 1'],
            ['id' => 'SR03', 'name' => 'Showroom Quận 7']
        ];
    }

    private function getSupplies()
    {
        return [
            ['code' => 'VT001', 'name' => 'Khung gỗ 30x40', 'unit' => 'm', 'qty' => 50],
            ['code' => 'VT002', 'name' => 'Khung gỗ 40x60', 'unit' => 'm', 'qty' => 30]
        ];
    }
}
