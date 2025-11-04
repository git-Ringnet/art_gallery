<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeesExport;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = User::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('employees.index', compact('employees', 'search', 'status', 'dateFrom', 'dateTo'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Vui lòng nhập tên nhân viên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email đã tồn tại trong hệ thống.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'avatar' => $avatarPath,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return redirect()->route('employees.index')
            ->with('success', 'Thêm nhân viên thành công');
    }

    public function show($id)
    {
        $employee = User::findOrFail($id);
        return view('employees.show', compact('employee'));
    }

    public function edit($id)
    {
        $employee = User::findOrFail($id);
        
        // Không cho phép chỉnh sửa tài khoản admin
        if ($employee->email === 'admin@example.com') {
            return redirect()->route('employees.index')
                ->with('error', 'Không thể chỉnh sửa tài khoản admin');
        }
        
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, $id)
    {
        $employee = User::findOrFail($id);
        
        // Không cho phép cập nhật tài khoản admin
        if ($employee->email === 'admin@example.com') {
            return redirect()->route('employees.index')
                ->with('error', 'Không thể cập nhật tài khoản admin');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
            'remove_avatar' => 'nullable|in:0,1',
        ], [
            'name.required' => 'Vui lòng nhập tên nhân viên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email đã tồn tại trong hệ thống.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        // Remove old avatar if requested
        if ($request->input('remove_avatar') === '1' && $employee->avatar) {
            Storage::disk('public')->delete($employee->avatar);
            $validated['avatar'] = null;
        }

        // Replace with new avatar
        if ($request->hasFile('avatar')) {
            if ($employee->avatar) {
                Storage::disk('public')->delete($employee->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        // Update password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Cập nhật nhân viên thành công');
    }

    public function destroy($id)
    {
        $employee = User::findOrFail($id);
        
        // Không cho phép xóa tài khoản admin
        if ($employee->email === 'admin@example.com') {
            return redirect()->route('employees.index')
                ->with('error', 'Không thể xóa tài khoản admin');
        }
        
        if ($employee->avatar) {
            Storage::disk('public')->delete($employee->avatar);
        }
        
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Đã xóa nhân viên');
    }

    public function toggleStatus($id)
    {
        $employee = User::findOrFail($id);
        
        // Không cho phép vô hiệu hóa tài khoản admin
        if ($employee->email === 'admin@example.com') {
            return redirect()->route('employees.index')
                ->with('error', 'Không thể thay đổi trạng thái tài khoản admin');
        }
        
        // Không cho phép vô hiệu hóa chính mình
        if (Auth::check() && $employee->id === Auth::id()) {
            return redirect()->route('employees.index')
                ->with('error', 'Không thể vô hiệu hóa tài khoản của chính bạn');
        }
        
        $employee->is_active = !$employee->is_active;
        $employee->save();
        
        $status = $employee->is_active ? 'kích hoạt' : 'vô hiệu hóa';
        
        return redirect()->route('employees.index')
            ->with('success', "Đã {$status} tài khoản thành công");
    }

    public function exportExcel(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $scope = $request->get('scope', 'all');
        $currentPage = (int) $request->get('page', 1);
        $perPage = 10;

        $query = User::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $employees = $query->orderBy('created_at', 'desc')->get();

        // If scope is 'current', only export current page
        if ($scope === 'current') {
            $employees = $employees->forPage($currentPage, $perPage);
        }

        $filename = 'danh-sach-nhan-vien-' . date('Y-m-d-His') . '.xlsx';
        
        return Excel::download(new EmployeesExport($employees), $filename);
    }

    public function exportPdf(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $scope = $request->get('scope', 'all');
        $currentPage = (int) $request->get('page', 1);
        $perPage = 10;

        $query = User::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $employees = $query->orderBy('created_at', 'desc')->get();

        // If scope is 'current', only export current page
        if ($scope === 'current') {
            $employees = $employees->forPage($currentPage, $perPage);
        }

        $pdf = Pdf::loadView('employees.export-pdf', compact('employees', 'search', 'status', 'dateFrom', 'dateTo', 'scope'));
        
        $filename = 'danh-sach-nhan-vien-' . date('Y-m-d-His') . '.pdf';
        return $pdf->download($filename);
    }
}
