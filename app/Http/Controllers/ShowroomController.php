<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShowroomController extends Controller
{
    public function index()
    {
        // Mock data - replace with actual database queries
        $showrooms = [
            [
                'id' => 'SR01',
                'code' => 'SR01',
                'name' => 'Showroom Trung tâm',
                'phone' => '0123 456 789',
                'address' => '123 Lê Lợi, Q.1',
                'bank_name' => 'Vietcombank',
                'bank_no' => '0123456789',
                'bank_holder' => 'Công ty TNHH ABC',
                'logo' => 'https://via.placeholder.com/48'
            ],
            [
                'id' => 'SR02',
                'code' => 'SR02',
                'name' => 'Showroom Quận 1',
                'phone' => '0987 654 321',
                'address' => '45 Pasteur, Q.1',
                'bank_name' => 'ACB',
                'bank_no' => '99887766',
                'bank_holder' => 'Công ty TNHH ABC',
                'logo' => 'https://via.placeholder.com/48'
            ],
            [
                'id' => 'SR03',
                'code' => 'SR03',
                'name' => 'Showroom Quận 7',
                'phone' => '0369 258 147',
                'address' => '68 Nguyễn Văn Linh, Q.7',
                'bank_name' => 'Techcombank',
                'bank_no' => '22334455',
                'bank_holder' => 'Công ty TNHH ABC',
                'logo' => 'https://via.placeholder.com/48'
            ]
        ];

        return view('showrooms.index', compact('showrooms'));
    }

    public function create()
    {
        return view('showrooms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:showrooms,code',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'bank_name' => 'nullable|string|max:100',
            'bank_no' => 'nullable|string|max:50',
            'bank_holder' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string'
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('showrooms', 'public');
            $validated['logo'] = $logoPath;
        }

        // Save showroom
        // This is where you'd save to database
        
        return redirect()->route('showrooms.index')
            ->with('success', 'Đã tạo phòng trưng bày thành công');
    }

    public function edit($id)
    {
        // Mock data - replace with actual database query
        $showroom = [
            'id' => $id,
            'code' => 'SR01',
            'name' => 'Showroom Trung tâm',
            'phone' => '0123 456 789',
            'address' => '123 Lê Lợi, Q.1',
            'bank_name' => 'Vietcombank',
            'bank_no' => '0123456789',
            'bank_holder' => 'Công ty TNHH ABC',
            'logo' => 'https://via.placeholder.com/48'
        ];

        return view('showrooms.edit', compact('showroom'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'bank_name' => 'nullable|string|max:100',
            'bank_no' => 'nullable|string|max:50',
            'bank_holder' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string'
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('showrooms', 'public');
            $validated['logo'] = $logoPath;
        }

        // Update showroom
        // This is where you'd update database
        
        return redirect()->route('showrooms.index')
            ->with('success', 'Đã cập nhật phòng trưng bày thành công');
    }

    public function destroy($id)
    {
        // Delete showroom
        // This is where you'd delete from database
        
        return redirect()->route('showrooms.index')
            ->with('success', 'Đã xóa phòng trưng bày thành công');
    }
}
