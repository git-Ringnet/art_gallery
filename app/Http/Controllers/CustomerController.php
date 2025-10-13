<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('has_debt')) {
            $query->withDebt();
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        $validated['total_purchased'] = 0;
        $validated['total_debt'] = 0;

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'Thêm khách hàng thành công!');
    }

    public function show(string $id)
    {
        $customer = Customer::with(['sales', 'debts'])->findOrFail($id);
        return view('customers.show', compact('customer'));
    }

    public function edit(string $id)
    {
        $customer = Customer::findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Cập nhật khách hàng thành công!');
    }

    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);
        
        // Kiểm tra có giao dịch bán hàng không
        if ($customer->sales()->count() > 0) {
            return redirect()->route('customers.index')
                ->with('error', 'Không thể xóa khách hàng đã có giao dịch bán hàng!');
        }

        // Kiểm tra có công nợ không
        if ($customer->total_debt > 0) {
            return redirect()->route('customers.index')
                ->with('error', 'Không thể xóa khách hàng đang có công nợ!');
        }

        // Kiểm tra có tổng mua hàng không
        if ($customer->total_purchased > 0) {
            return redirect()->route('customers.index')
                ->with('error', 'Không thể xóa khách hàng đã có lịch sử mua hàng!');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Xóa khách hàng thành công!');
    }
}
