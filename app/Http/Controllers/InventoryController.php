<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $type = $request->get('type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Mock data - replace with actual database queries
        $inventory = [
            [
                'code' => 'T001',
                'name' => 'Tranh sơn dầu phong cảnh',
                'type' => 'painting',
                'quantity' => 15,
                'import_date' => '01/12/2024',
                'status' => 'in_stock'
            ],
            [
                'code' => 'VT001',
                'name' => 'Khung gỗ 30x40',
                'type' => 'supply',
                'quantity' => 50,
                'unit' => 'm',
                'import_date' => '05/12/2024',
                'status' => 'in_stock'
            ]
        ];

        return view('inventory.index', compact('inventory'));
    }

    public function import()
    {
        return view('inventory.import');
    }

    public function importPainting(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'artist' => 'required|string|max:255',
            'material' => 'required|string|max:100',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'year' => 'nullable|numeric',
            'price' => 'required|numeric|min:0',
            'import_date' => 'required|date',
            'export_date' => 'nullable|date|after:import_date',
            'notes' => 'nullable|string'
        ]);

        // Save painting import
        // This is where you'd save to database
        
        return redirect()->route('inventory.index')
            ->with('success', 'Đã nhập tranh thành công');
    }

    public function importSupply(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'unit' => 'required|string|max:20',
            'quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        // Save supply import
        // This is where you'd save to database
        
        return redirect()->route('inventory.index')
            ->with('success', 'Đã nhập vật tư thành công');
    }
}
