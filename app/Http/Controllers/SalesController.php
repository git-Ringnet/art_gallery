<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Showroom;
use App\Models\Painting;
use App\Models\Supply;
use App\Models\Payment;
use App\Models\Debt;
use App\Models\ExchangeRate;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    /**
     * Get or create default user for sales
     */
    private function getDefaultUser()
    {
        // Try to get authenticated user first
        if (Auth::check()) {
            return Auth::user();
        }

        // Get first user or create default one
        $defaultUser = User::first();
        if (!$defaultUser) {
            $defaultUser = User::create([
                'name' => 'Nhân viên mặc định',
                'email' => 'default@gallery.com',
                'password' => bcrypt('password123'),
            ]);
        }

        return $defaultUser;
    }

    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'showroom', 'user'])
            ->orderBy('created_at', 'desc'); // Phiếu mới tạo lên trên

        // Search - tìm theo mã HD, tên KH, SĐT, email, sản phẩm
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_code', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('phone', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('saleItems', function($itemQuery) use ($search) {
                      $itemQuery->where('description', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by showroom
        if ($request->filled('showroom_id')) {
            $query->where('showroom_id', $request->showroom_id);
        }

        // Filter by user (nhân viên bán)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('sale_date', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('sale_date', '<=', $request->to_date);
        }

        // Filter by amount range
        if ($request->filled('min_amount')) {
            $query->where('total_vnd', '>=', $request->min_amount);
        }
        
        if ($request->filled('max_amount')) {
            $query->where('total_vnd', '<=', $request->max_amount);
        }

        // Filter by debt status
        if ($request->filled('has_debt')) {
            if ($request->has_debt == '1') {
                $query->where('debt_amount', '>', 0);
            } elseif ($request->has_debt == '0') {
                $query->where('debt_amount', '=', 0);
            }
        }

        // Sort
        $sortBy = $request->get('sort_by', 'sale_date');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['sale_date', 'total_vnd', 'paid_amount', 'debt_amount', 'invoice_code'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $sales = $query->paginate(20)->withQueryString();

        // Get filter options
        $showrooms = Showroom::active()->get();
        $users = User::all();

        return view('sales.index', compact('sales', 'showrooms', 'users'));
    }

    public function create()
    {
        $showrooms = Showroom::active()->get();
        $supplies = Supply::all();
        $paintings = Painting::available()->get();
        $customers = Customer::all();
        $currentRate = ExchangeRate::getCurrentRate();

        return view('sales.create', compact('showrooms', 'supplies', 'paintings', 'customers', 'currentRate'));
    }

    public function store(Request $request)
    {
        Log::info('=== SALES STORE START ===');
        Log::info('Request data:', $request->all());
        
        // Add invoice_code validation
        $request->merge(['invoice_code' => $request->invoice_code ?: null]);
        
        // Validate invoice_code separately
        if ($request->invoice_code) {
            $request->validate([
                'invoice_code' => 'string|max:50|unique:sales,invoice_code'
            ], [
                'invoice_code.string' => 'Số hóa đơn phải là chuỗi ký tự',
                'invoice_code.max' => 'Số hóa đơn không được quá 50 ký tự',
                'invoice_code.unique' => 'Số hóa đơn này đã tồn tại'
            ]);
        }
        
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|string|max:255',
            'customer_phone' => 'required_without:customer_id|string|max:20',
            'customer_email' => 'nullable|email',
            'customer_address' => 'nullable|string',
            'showroom_id' => 'required|exists:showrooms,id',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.painting_id' => 'nullable|exists:paintings,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.supply_id' => 'nullable|exists:supplies,id',
            'items.*.supply_length' => 'nullable|numeric|min:0',
            'items.*.currency' => 'required|in:USD,VND',
            'items.*.price_usd' => 'nullable|numeric|min:0',
            'items.*.price_vnd' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'exchange_rate' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,card,other',
            'notes' => 'nullable|string',
        ], [
            'customer_id.exists' => 'Khách hàng không tồn tại',
            'customer_name.required_without' => 'Tên khách hàng là bắt buộc',
            'customer_name.string' => 'Tên khách hàng phải là chuỗi ký tự',
            'customer_name.max' => 'Tên khách hàng không được quá 255 ký tự',
            'customer_phone.required_without' => 'Số điện thoại là bắt buộc',
            'customer_phone.string' => 'Số điện thoại phải là chuỗi ký tự',
            'customer_phone.max' => 'Số điện thoại không được quá 20 ký tự',
            'customer_email.email' => 'Email không đúng định dạng',
            'customer_address.string' => 'Địa chỉ phải là chuỗi ký tự',
            'showroom_id.required' => 'Showroom là bắt buộc',
            'showroom_id.exists' => 'Showroom không tồn tại',
            'sale_date.required' => 'Ngày bán là bắt buộc',
            'sale_date.date' => 'Ngày bán không đúng định dạng',
            'items.required' => 'Danh sách sản phẩm là bắt buộc',
            'items.array' => 'Danh sách sản phẩm phải là mảng',
            'items.min' => 'Phải có ít nhất 1 sản phẩm',
            'items.*.painting_id.exists' => 'Tranh không tồn tại',
            'items.*.description.required' => 'Mô tả sản phẩm là bắt buộc',
            'items.*.description.string' => 'Mô tả sản phẩm phải là chuỗi ký tự',
            'items.*.quantity.required' => 'Số lượng là bắt buộc',
            'items.*.quantity.numeric' => 'Số lượng phải là số',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0',
            'items.*.supply_id.exists' => 'Vật tư không tồn tại',
            'items.*.supply_length.numeric' => 'Chiều dài vật tư phải là số',
            'items.*.supply_length.min' => 'Chiều dài vật tư phải lớn hơn hoặc bằng 0',
            'items.*.currency.required' => 'Loại tiền tệ là bắt buộc',
            'items.*.currency.in' => 'Loại tiền tệ không hợp lệ',
            'items.*.price_usd.numeric' => 'Giá USD phải là số',
            'items.*.price_usd.min' => 'Giá USD phải lớn hơn hoặc bằng 0',
            'items.*.price_vnd.numeric' => 'Giá VND phải là số',
            'items.*.price_vnd.min' => 'Giá VND phải lớn hơn hoặc bằng 0',
            'exchange_rate.required' => 'Tỷ giá là bắt buộc',
            'exchange_rate.numeric' => 'Tỷ giá phải là số',
            'exchange_rate.min' => 'Tỷ giá phải lớn hơn 0',
            'discount_percent.numeric' => 'Phần trăm giảm giá phải là số',
            'discount_percent.min' => 'Phần trăm giảm giá phải lớn hơn hoặc bằng 0',
            'discount_percent.max' => 'Phần trăm giảm giá không được quá 100',
            'payment_amount.numeric' => 'Số tiền thanh toán phải là số',
            'payment_amount.min' => 'Số tiền thanh toán phải lớn hơn hoặc bằng 0',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ',
            'notes.string' => 'Ghi chú phải là chuỗi ký tự',
        ]);

        Log::info('Validation passed');
        Log::info('Validated data:', $validated);

        DB::beginTransaction();
        try {
            // Create or get customer
            if ($request->filled('customer_id')) {
                $customer = Customer::find($request->customer_id);
            } else {
                $customer = Customer::create([
                    'name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'email' => $request->customer_email,
                    'address' => $request->customer_address,
                ]);
            }

            // Get default user
            $user = $this->getDefaultUser();

            // Create sale
            $sale = Sale::create([
                'invoice_code' => $request->invoice_code ?: Sale::generateInvoiceCode(),
                'customer_id' => $customer->id,
                'showroom_id' => $request->showroom_id,
                'user_id' => $user->id,
                'sale_date' => $request->sale_date,
                'exchange_rate' => $request->exchange_rate,
                'discount_percent' => $request->discount_percent ?? 0,
                'subtotal_usd' => 0,
                'subtotal_vnd' => 0,
                'total_usd' => 0,
                'total_vnd' => 0,
                'paid_amount' => 0,
                'debt_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => $request->notes,
            ]);

            // Create sale items
            foreach ($request->items as $item) {
                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'painting_id' => $item['painting_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'supply_id' => $item['supply_id'] ?? null,
                    'supply_length' => $item['supply_length'] ?? null,
                    'currency' => $item['currency'],
                    'price_usd' => $item['currency'] === 'USD' ? ($item['price_usd'] ?? 0) : 0,
                    'price_vnd' => $item['currency'] === 'VND' ? ($item['price_vnd'] ?? 0) : 0,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                ]);

                // Calculate totals
                $saleItem->calculateTotals();

                // Process painting stock - TRỪ KHO
                if ($saleItem->painting_id) {
                    $painting = Painting::find($saleItem->painting_id);
                    if ($painting && $painting->reduceQuantity($saleItem->quantity)) {
                        InventoryTransaction::create([
                            'transaction_type' => 'export',
                            'item_type' => 'painting',
                            'item_id' => $saleItem->painting_id,
                            'quantity' => $saleItem->quantity,
                            'reference_type' => 'sale',
                            'reference_id' => $sale->id,
                            'transaction_date' => $sale->sale_date,
                            'notes' => "Bán trong hóa đơn {$sale->invoice_code}",
                            'created_by' => $user->id,
                        ]);
                    }
                }

                // Process supply usage
                if ($saleItem->supply_id && $saleItem->supply_length) {
                    $supply = Supply::find($saleItem->supply_id);
                    $totalRequired = $saleItem->supply_length * $saleItem->quantity;
                    
                    if ($supply && $supply->reduceQuantity($totalRequired)) {
                        InventoryTransaction::create([
                            'transaction_type' => 'export',
                            'item_type' => 'supply',
                            'item_id' => $saleItem->supply_id,
                            'quantity' => $totalRequired,
                            'reference_type' => 'sale',
                            'reference_id' => $sale->id,
                            'transaction_date' => $sale->sale_date,
                            'notes' => "Sử dụng cho hóa đơn {$sale->invoice_code}",
                            'created_by' => $user->id,
                        ]);
                    }
                }
            }

            // Calculate sale totals
            $sale->calculateTotals();

            // Create payment if provided
            if ($request->filled('payment_amount') && $request->payment_amount > 0) {
                Payment::create([
                    'sale_id' => $sale->id,
                    'amount' => $request->payment_amount,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'payment_date' => $request->sale_date,
                    'created_by' => $user->id,
                ]);
            }

            // Create debt if there's remaining amount
            if ($sale->debt_amount > 0) {
                Debt::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $customer->id,
                    'total_amount' => $sale->total_vnd,
                    'paid_amount' => $sale->paid_amount,
                    'debt_amount' => $sale->debt_amount,
                    'due_date' => now()->addDays(30),
                    'status' => 'unpaid',
                ]);
            }

            DB::commit();

            // Check if user wants to print
            if ($request->input('action') === 'save_and_print') {
                return redirect()->route('sales.print', $sale->id)
                    ->with('success', "Hóa đơn {$sale->invoice_code} đã được tạo thành công");
            }

            return redirect()->route('sales.index')
                ->with('success', "Hóa đơn {$sale->invoice_code} đã được tạo thành công");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sales store error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $sale = Sale::with(['customer', 'showroom', 'user', 'saleItems.painting', 'saleItems.supply', 'payments', 'debt'])
            ->findOrFail($id);

        return view('sales.show', compact('sale'));
    }

    public function edit($id)
    {
        $sale = Sale::with(['saleItems.painting', 'saleItems.supply', 'customer', 'payments'])->findOrFail($id);
        $showrooms = Showroom::active()->get();
        $supplies = Supply::all();
        $paintings = Painting::available()->get();
        $customers = Customer::all();
        $currentRate = ExchangeRate::getCurrentRate();

        return view('sales.edit', compact('sale', 'showrooms', 'supplies', 'paintings', 'customers', 'currentRate'));
    }

    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);

        // Add invoice_code validation for update
        $request->merge(['invoice_code' => $request->invoice_code ?: null]);
        
        // Validate invoice_code separately for update
        if ($request->invoice_code) {
            $request->validate([
                'invoice_code' => 'string|max:50|unique:sales,invoice_code,' . $id
            ], [
                'invoice_code.string' => 'Số hóa đơn phải là chuỗi ký tự',
                'invoice_code.max' => 'Số hóa đơn không được quá 50 ký tự',
                'invoice_code.unique' => 'Số hóa đơn này đã tồn tại'
            ]);
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|string|max:255',
            'customer_phone' => 'required_without:customer_id|string|max:20',
            'customer_email' => 'nullable|email',
            'customer_address' => 'nullable|string',
            'showroom_id' => 'required|exists:showrooms,id',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.painting_id' => 'nullable|exists:paintings,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.supply_id' => 'nullable|exists:supplies,id',
            'items.*.supply_length' => 'nullable|numeric|min:0',
            'items.*.currency' => 'required|in:USD,VND',
            'items.*.price_usd' => 'nullable|numeric|min:0',
            'items.*.price_vnd' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'exchange_rate' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,card,other',
            'notes' => 'nullable|string',
        ], [
            'customer_id.exists' => 'Khách hàng không tồn tại',
            'customer_name.required_without' => 'Tên khách hàng là bắt buộc',
            'customer_name.string' => 'Tên khách hàng phải là chuỗi ký tự',
            'customer_name.max' => 'Tên khách hàng không được quá 255 ký tự',
            'customer_phone.required_without' => 'Số điện thoại là bắt buộc',
            'customer_phone.string' => 'Số điện thoại phải là chuỗi ký tự',
            'customer_phone.max' => 'Số điện thoại không được quá 20 ký tự',
            'customer_email.email' => 'Email không đúng định dạng',
            'customer_address.string' => 'Địa chỉ phải là chuỗi ký tự',
            'showroom_id.required' => 'Showroom là bắt buộc',
            'showroom_id.exists' => 'Showroom không tồn tại',
            'sale_date.required' => 'Ngày bán là bắt buộc',
            'sale_date.date' => 'Ngày bán không đúng định dạng',
            'items.required' => 'Danh sách sản phẩm là bắt buộc',
            'items.array' => 'Danh sách sản phẩm phải là mảng',
            'items.min' => 'Phải có ít nhất 1 sản phẩm',
            'items.*.painting_id.exists' => 'Tranh không tồn tại',
            'items.*.description.required' => 'Mô tả sản phẩm là bắt buộc',
            'items.*.description.string' => 'Mô tả sản phẩm phải là chuỗi ký tự',
            'items.*.quantity.required' => 'Số lượng là bắt buộc',
            'items.*.quantity.numeric' => 'Số lượng phải là số',
            'items.*.quantity.min' => 'Số lượng phải lớn hơn 0',
            'items.*.supply_id.exists' => 'Vật tư không tồn tại',
            'items.*.supply_length.numeric' => 'Chiều dài vật tư phải là số',
            'items.*.supply_length.min' => 'Chiều dài vật tư phải lớn hơn hoặc bằng 0',
            'items.*.currency.required' => 'Loại tiền tệ là bắt buộc',
            'items.*.currency.in' => 'Loại tiền tệ không hợp lệ',
            'items.*.price_usd.numeric' => 'Giá USD phải là số',
            'items.*.price_usd.min' => 'Giá USD phải lớn hơn hoặc bằng 0',
            'items.*.price_vnd.numeric' => 'Giá VND phải là số',
            'items.*.price_vnd.min' => 'Giá VND phải lớn hơn hoặc bằng 0',
            'exchange_rate.required' => 'Tỷ giá là bắt buộc',
            'exchange_rate.numeric' => 'Tỷ giá phải là số',
            'exchange_rate.min' => 'Tỷ giá phải lớn hơn 0',
            'discount_percent.numeric' => 'Phần trăm giảm giá phải là số',
            'discount_percent.min' => 'Phần trăm giảm giá phải lớn hơn hoặc bằng 0',
            'discount_percent.max' => 'Phần trăm giảm giá không được quá 100',
            'payment_amount.numeric' => 'Số tiền thanh toán phải là số',
            'payment_amount.min' => 'Số tiền thanh toán phải lớn hơn hoặc bằng 0',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ',
            'notes.string' => 'Ghi chú phải là chuỗi ký tự',
        ]);

        DB::beginTransaction();
        try {
            // Create or get customer
            if ($request->filled('customer_id')) {
                $customer = Customer::find($request->customer_id);
            } else {
                $customer = Customer::create([
                    'name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'email' => $request->customer_email,
                    'address' => $request->customer_address,
                ]);
            }

            // Get default user
            $user = $this->getDefaultUser();

            // Update sale
            $sale->update([
                'customer_id' => $customer->id,
                'showroom_id' => $request->showroom_id,
                'user_id' => $user->id,
                'sale_date' => $request->sale_date,
                'exchange_rate' => $request->exchange_rate,
                'discount_percent' => $request->discount_percent ?? 0,
                'notes' => $request->notes,
                'invoice_code' => $request->invoice_code ?: $sale->invoice_code,
            ]);

            // Hoàn trả kho từ items cũ trước khi xóa
            foreach ($sale->saleItems as $oldItem) {
                if ($oldItem->painting_id) {
                    $painting = Painting::find($oldItem->painting_id);
                    if ($painting) {
                        $painting->increaseQuantity($oldItem->quantity);
                    }
                }
                
                if ($oldItem->supply_id && $oldItem->supply_length) {
                    $supply = Supply::find($oldItem->supply_id);
                    if ($supply) {
                        $totalUsed = $oldItem->supply_length * $oldItem->quantity;
                        $supply->increaseQuantity($totalUsed);
                    }
                }
            }
            
            // Delete existing sale items
            $sale->saleItems()->delete();

            // Create new sale items
            foreach ($request->items as $item) {
                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'painting_id' => $item['painting_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'supply_id' => $item['supply_id'] ?? null,
                    'supply_length' => $item['supply_length'] ?? null,
                    'currency' => $item['currency'],
                    'price_usd' => $item['currency'] === 'USD' ? ($item['price_usd'] ?? 0) : 0,
                    'price_vnd' => $item['currency'] === 'VND' ? ($item['price_vnd'] ?? 0) : 0,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                ]);

                // Calculate totals
                $saleItem->calculateTotals();

                // Process painting stock - TRỪ KHO
                if ($saleItem->painting_id) {
                    $painting = Painting::find($saleItem->painting_id);
                    if ($painting && $painting->reduceQuantity($saleItem->quantity)) {
                        InventoryTransaction::create([
                            'transaction_type' => 'export',
                            'item_type' => 'painting',
                            'item_id' => $saleItem->painting_id,
                            'quantity' => $saleItem->quantity,
                            'reference_type' => 'sale',
                            'reference_id' => $sale->id,
                            'transaction_date' => $sale->sale_date,
                            'notes' => "Bán trong hóa đơn {$sale->invoice_code}",
                            'created_by' => $user->id,
                        ]);
                    }
                }

                // Process supply usage
                if ($saleItem->supply_id && $saleItem->supply_length) {
                    $supply = Supply::find($saleItem->supply_id);
                    $totalRequired = $saleItem->supply_length * $saleItem->quantity;
                    
                    if ($supply && $supply->reduceQuantity($totalRequired)) {
                        InventoryTransaction::create([
                            'transaction_type' => 'export',
                            'item_type' => 'supply',
                            'item_id' => $saleItem->supply_id,
                            'quantity' => $totalRequired,
                            'reference_type' => 'sale',
                            'reference_id' => $sale->id,
                            'transaction_date' => $sale->sale_date,
                            'notes' => "Sử dụng cho hóa đơn {$sale->invoice_code}",
                            'created_by' => $user->id,
                        ]);
                    }
                }
            }

            // Calculate sale totals
            $sale->calculateTotals();

            // Add new payment if provided (thêm payment mới, không update cũ)
            if ($request->filled('payment_amount') && $request->payment_amount > 0) {
                Payment::create([
                    'sale_id' => $sale->id,
                    'amount' => $request->payment_amount,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'payment_date' => now(),
                    'notes' => 'Trả nợ thêm',
                    'created_by' => $user->id,
                ]);
            }

            // Update debt if there's remaining amount
            $existingDebt = $sale->debt()->first();
            if ($sale->debt_amount > 0) {
                if ($existingDebt) {
                    $existingDebt->update([
                        'customer_id' => $customer->id,
                        'total_amount' => $sale->total_vnd,
                        'paid_amount' => $sale->paid_amount,
                        'debt_amount' => $sale->debt_amount,
                    ]);
                } else {
                    Debt::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $customer->id,
                        'total_amount' => $sale->total_vnd,
                        'paid_amount' => $sale->paid_amount,
                        'debt_amount' => $sale->debt_amount,
                        'due_date' => now()->addDays(30),
                        'status' => 'unpaid',
                    ]);
                }
            } else if ($existingDebt) {
                $existingDebt->delete();
            }

            DB::commit();

            return redirect()->route('sales.show', $sale->id)
                ->with('success', 'Hóa đơn đã được cập nhật thành công');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sales update error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        Log::info('Delete request for sale ID: ' . $id);
        
        $sale = Sale::findOrFail($id);
        Log::info('Sale found: ' . $sale->invoice_code . ', Paid amount: ' . $sale->paid_amount);

        // Check if sale can be deleted (no payments made)
        if ($sale->paid_amount > 0) {
            Log::info('Cannot delete sale with payments');
            return back()->with('error', 'Không thể xóa hóa đơn đã có thanh toán');
        }

        DB::beginTransaction();
        try {
            // Restore painting quantities
            foreach ($sale->saleItems as $item) {
                if ($item->painting_id) {
                    $painting = Painting::find($item->painting_id);
                    $painting->increaseQuantity($item->quantity);
                }

                if ($item->supply_id && $item->supply_length) {
                    $supply = Supply::find($item->supply_id);
                    $totalUsed = $item->supply_length * $item->quantity;
                    $supply->increaseQuantity($totalUsed);
                }
            }

            // Delete related records
            $sale->saleItems()->delete();
            $sale->debt()->delete();
            $sale->delete();

            DB::commit();
            Log::info('Sale deleted successfully');

            return redirect()->route('sales.index')
                ->with('success', 'Hóa đơn đã được xóa');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete error: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $sale = Sale::with(['customer', 'showroom', 'saleItems.painting', 'saleItems.supply'])
            ->findOrFail($id);

        return view('sales.print', compact('sale'));
    }

    // API endpoints for AJAX
    public function getPainting($id)
    {
        $painting = Painting::findOrFail($id);
        return response()->json($painting);
    }

    public function getSupply($id)
    {
        $supply = Supply::findOrFail($id);
        return response()->json($supply);
    }

    public function getCustomer($id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json($customer);
    }

    public function searchPaintings(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $paintings = Painting::where(function($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'code', 'name', 'price_usd', 'price_vnd', 'quantity', 'image']);

        return response()->json($paintings);
    }

    public function searchSupplies(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $supplies = Supply::where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'unit', 'quantity']);

        return response()->json($supplies);
    }

    // API: Search suggestions cho trang index
    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        $suggestions = [];

        // Tìm theo mã hóa đơn
        $invoices = Sale::where('invoice_code', 'like', "%{$query}%")
            ->with('customer')
            ->limit(5)
            ->get();
        
        foreach ($invoices as $invoice) {
            $suggestions[] = [
                'type' => 'invoice',
                'icon' => 'fa-file-invoice',
                'label' => $invoice->invoice_code,
                'sublabel' => $invoice->customer->name . ' - ' . number_format($invoice->total_vnd) . 'đ',
                'value' => $invoice->invoice_code,
                'url' => route('sales.show', $invoice->id)
            ];
        }

        // Tìm theo khách hàng
        $customers = Customer::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->withCount('sales')
            ->limit(5)
            ->get();
        
        foreach ($customers as $customer) {
            $suggestions[] = [
                'type' => 'customer',
                'icon' => 'fa-user',
                'label' => $customer->name,
                'sublabel' => $customer->phone . ' - ' . $customer->sales_count . ' đơn hàng',
                'value' => $customer->name,
                'search' => $customer->name
            ];
        }

        return response()->json($suggestions);
    }
}

