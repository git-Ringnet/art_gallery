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
            'exchange_rate' => 'required|numeric|min:1',
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

            // Lấy số tiền đã trả (nếu có)
            $paidAmount = $request->filled('payment_amount') && $request->payment_amount > 0 
                ? $request->payment_amount 
                : 0;

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
                'paid_amount' => $paidAmount,
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

                // KHÔNG TRỪ KHO Ở ĐÂY - Sẽ trừ khi duyệt phiếu (approve)
            }

            // Calculate sale totals
            $sale->calculateTotals();
            
            // Lưu original_total (giá trị gốc ban đầu)
            $sale->update([
                'original_total_vnd' => $sale->total_vnd,
                'original_total_usd' => $sale->total_usd,
            ]);

            // KHÔNG tạo payment khi tạo phiếu pending
            // Payment sẽ được tạo khi duyệt phiếu (approve)

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
        
        // Cho phép edit cả khi đã duyệt (để thêm thanh toán)
        if (!$sale->canEdit()) {
            return redirect()->route('sales.show', $id)
                ->with('error', 'Không thể sửa phiếu đã hủy.');
        }
        
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
        
        // Cho phép edit khi chưa hủy
        if (!$sale->canEdit()) {
            return back()->with('error', 'Không thể sửa phiếu đã hủy');
        }

        // Kiểm tra xem có return/exchange không
        $hasReturns = $sale->returns()->whereIn('status', ['approved', 'completed'])->exists();

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

        // Validation rules khác nhau tùy theo có return hay không
        $rules = [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|string|max:255',
            'customer_phone' => 'required_without:customer_id|string|max:20',
            'customer_email' => 'nullable|email',
            'customer_address' => 'nullable|string',
            'showroom_id' => 'required|exists:showrooms,id',
            'sale_date' => 'required|date',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,card,other',
            'notes' => 'nullable|string',
        ];

        // Chỉ validate items nếu CHƯA có return
        if (!$hasReturns) {
            $rules = array_merge($rules, [
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
                'exchange_rate' => 'required|numeric|min:1',
                'discount_percent' => 'nullable|numeric|min:0|max:100',
            ]);
        }

        $validated = $request->validate($rules, [
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

            // Nếu CHƯA có return: Cho phép sửa sản phẩm
            if (!$hasReturns) {
                // Hoàn trả kho từ items cũ trước khi xóa (nếu phiếu đã duyệt)
                if ($sale->isCompleted()) {
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

                    // Trừ kho ngay nếu phiếu đã duyệt
                    if ($sale->isCompleted()) {
                        if ($saleItem->painting_id) {
                            $painting = Painting::find($saleItem->painting_id);
                            if ($painting && !$painting->reduceQuantity($saleItem->quantity)) {
                                throw new \Exception("Không đủ số lượng tranh {$painting->name} trong kho");
                            }
                        }
                        
                        if ($saleItem->supply_id && $saleItem->supply_length) {
                            $supply = Supply::find($saleItem->supply_id);
                            $totalRequired = $saleItem->supply_length * $saleItem->quantity;
                            if ($supply && !$supply->reduceQuantity($totalRequired)) {
                                throw new \Exception("Không đủ số lượng vật tư {$supply->name} trong kho");
                            }
                        }
                    }
                }

                // Calculate sale totals
                $sale->calculateTotals();
                
                // Cập nhật original_total nếu chưa có (cho phiếu cũ)
                if (!$sale->original_total_vnd) {
                    $sale->update([
                        'original_total_vnd' => $sale->total_vnd,
                        'original_total_usd' => $sale->total_usd,
                    ]);
                }
            }
            // Nếu ĐÃ có return: Chỉ xử lý thanh toán, không động đến sale_items

            // Xử lý thanh toán
            if ($request->filled('payment_amount') && $request->payment_amount > 0) {
                if ($sale->sale_status === 'completed') {
                    // Phiếu đã duyệt - tạo payment mới (trả thêm)
                    Payment::create([
                        'sale_id' => $sale->id,
                        'amount' => $request->payment_amount,
                        'payment_method' => $request->payment_method ?? 'cash',
                        'transaction_type' => 'sale_payment',
                        'payment_date' => now(),
                        'notes' => 'Trả nợ thêm',
                        'created_by' => $user->id,
                    ]);
                } else {
                    // Phiếu pending - cập nhật paid_amount trong sale (chưa tạo payment)
                    $sale->paid_amount = $request->payment_amount;
                    $sale->debt_amount = $sale->total_vnd - $sale->paid_amount;
                    $sale->save();
                }
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
        Log::info('Sale found: ' . $sale->invoice_code . ', Status: ' . $sale->sale_status . ', Paid amount: ' . $sale->paid_amount);

        // Chỉ không cho xóa nếu đã có thanh toán
        // Phiếu completed nhưng chưa thanh toán vẫn có thể xóa
        if ($sale->paid_amount > 0) {
            Log::info('Cannot delete sale with payments');
            return back()->with('error', 'Không thể xóa hóa đơn đã có thanh toán');
        }

        DB::beginTransaction();
        try {
            // Chỉ hoàn kho nếu phiếu đã duyệt (completed)
            // Phiếu "Chờ duyệt" chưa trừ kho nên không cần hoàn
            if ($sale->sale_status === 'completed') {
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

    public function getCustomerDebt($id)
    {
        $customer = Customer::findOrFail($id);
        
        // Tính tổng công nợ hiện tại của khách hàng
        // Lấy từ tất cả các sales chưa thanh toán hết
        $totalDebt = $customer->sales()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->sum('debt_amount');
        
        return response()->json([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'total_debt' => $totalDebt
        ]);
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

    public function approve($id)
    {
        $sale = Sale::with('saleItems')->findOrFail($id);

        // Check if sale can be approved
        if (!$sale->canApprove()) {
            return back()->with('error', 'Phiếu bán hàng này không thể duyệt');
        }

        DB::beginTransaction();
        try {
            $user = $this->getDefaultUser();

            // Trừ kho khi duyệt phiếu
            foreach ($sale->saleItems as $saleItem) {
                // Process painting stock
                if ($saleItem->painting_id) {
                    $painting = Painting::find($saleItem->painting_id);
                    if ($painting) {
                        if (!$painting->reduceQuantity($saleItem->quantity)) {
                            throw new \Exception("Không đủ số lượng tranh {$painting->name} trong kho");
                        }
                        
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
                    
                    if ($supply) {
                        if (!$supply->reduceQuantity($totalRequired)) {
                            throw new \Exception("Không đủ số lượng vật tư {$supply->name} trong kho");
                        }
                        
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

            // Update sale status to completed
            $sale->update(['sale_status' => 'completed']);

            // Tạo payment record từ paid_amount ban đầu (nếu có)
            if ($sale->paid_amount > 0) {
                Payment::create([
                    'sale_id' => $sale->id,
                    'amount' => $sale->paid_amount,
                    'payment_method' => 'cash', // Default
                    'transaction_type' => 'sale_payment',
                    'payment_date' => $sale->sale_date,
                    'notes' => 'Thanh toán ban đầu khi duyệt phiếu',
                    'created_by' => $user->id,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Đã duyệt phiếu bán hàng ' . $sale->invoice_code . ' và trừ kho thành công');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approve sale error: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function cancel($id)
    {
        $sale = Sale::findOrFail($id);

        // Chỉ cho hủy khi phiếu chờ duyệt
        if (!$sale->isPending()) {
            return back()->with('error', 'Chỉ có thể hủy phiếu đang chờ duyệt');
        }

        // Không cho hủy nếu đã có thanh toán
        if ($sale->paid_amount > 0) {
            return back()->with('error', 'Không thể hủy phiếu đã có thanh toán');
        }

        DB::beginTransaction();
        try {
            // Update sale status to cancelled
            $sale->update(['sale_status' => 'cancelled']);

            DB::commit();

            return back()->with('success', 'Đã hủy phiếu bán hàng ' . $sale->invoice_code);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cancel sale error: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}

