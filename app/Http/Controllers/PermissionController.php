<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        // Mock data - replace with actual database queries
        $roles = [
            [
                'name' => 'Admin',
                'permissions' => ['dashboard', 'sales', 'debt', 'returns', 'inventory', 'showrooms', 'permissions']
            ],
            [
                'name' => 'Nhân viên bán hàng',
                'permissions' => ['dashboard', 'sales', 'returns']
            ],
            [
                'name' => 'Thủ kho',
                'permissions' => ['dashboard', 'inventory']
            ]
        ];

        $modules = [
            'dashboard' => 'Báo cáo thống kê',
            'sales' => 'Bán hàng',
            'debt' => 'Lịch sử công nợ',
            'returns' => 'Đổi/Trả hàng',
            'inventory' => 'Quản lý kho',
            'showrooms' => 'Phòng trưng bày'
        ];

        return view('permissions.index', compact('roles', 'modules'));
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string'
        ]);

        // Save role
        // This is where you'd save to database
        
        return redirect()->route('permissions.index')
            ->with('success', 'Đã tạo vai trò thành công');
    }

    public function updateRole(Request $request, $name)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string'
        ]);

        // Update role permissions
        // This is where you'd update database
        
        return redirect()->route('permissions.index')
            ->with('success', 'Đã cập nhật quyền thành công');
    }
}
