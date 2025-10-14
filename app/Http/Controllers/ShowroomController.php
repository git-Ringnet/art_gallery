<?php

namespace App\Http\Controllers;

use App\Models\Showroom;
use Illuminate\Http\Request;

class ShowroomController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $showrooms = Showroom::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();
            
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
            'bank_account' => 'nullable|string|max:50',
            'bank_holder' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string'
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('showrooms', 'public');
        }

        Showroom::create($validated);
        
        return redirect()->route('showrooms.index')
            ->with('success', 'Đã tạo phòng trưng bày thành công');
    }

    public function edit($id)
    {
        $showroom = Showroom::findOrFail($id);
        return view('showrooms.edit', compact('showroom'));
    }

    public function update(Request $request, $id)
    {
        $showroom = Showroom::findOrFail($id);
        
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:showrooms,code,' . $id,
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'bank_holder' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string'
        ]);

        // Xử lý xóa logo
        if ($request->input('remove_logo') == '1') {
            if ($showroom->logo && \Storage::disk('public')->exists($showroom->logo)) {
                \Storage::disk('public')->delete($showroom->logo);
            }
            $validated['logo'] = null;
        }

        // Xử lý upload logo mới
        if ($request->hasFile('logo')) {
            if ($showroom->logo && \Storage::disk('public')->exists($showroom->logo)) {
                \Storage::disk('public')->delete($showroom->logo);
            }
            $validated['logo'] = $request->file('logo')->store('showrooms', 'public');
        }

        $showroom->update($validated);
        
        return redirect()->route('showrooms.index')
            ->with('success', 'Đã cập nhật phòng trưng bày thành công');
    }

    public function destroy($id)
    {
        $showroom = Showroom::findOrFail($id);
        $showroom->delete();
        
        return redirect()->route('showrooms.index')
            ->with('success', 'Đã xóa phòng trưng bày thành công');
    }
}
