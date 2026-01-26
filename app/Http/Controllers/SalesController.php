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
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesController extends Controller
{
    protected $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

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
        $query = Sale::with(['customer', 'showroom', 'user', 'payments'])
            ->orderBy('created_at', 'desc'); // Phiếu mới tạo lên trên

        // Lọc theo năm đang chọn
        $selectedYear = session('selected_year', date('Y'));
        $query->where('year', $selectedYear);

        // Áp dụng phạm vi dữ liệu theo phân quyền
        $query = \App\Helpers\PermissionHelper::applyDataScope($query, 'sales', 'user_id', 'showroom_id');

        // Search - tìm theo mã HD, tên KH, SĐT, email, sản phẩm (nếu có quyền)
        if ($request->filled('search') && \App\Helpers\PermissionHelper::canSearch('sales')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('saleItems', function ($itemQuery) use ($search) {
                        $itemQuery->where('description', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by payment status (nếu có quyền lọc theo trạng thái)
        if ($request->filled('payment_status')) {
            $canFilterStatus = Auth::user()->email === 'admin@example.com' ||
                (Auth::user()->role && Auth::user()->role->getModulePermissions('sales') && Auth::user()->role->getModulePermissions('sales')->can_filter_by_status);
            if ($canFilterStatus) {
                $query->where('payment_status', $request->payment_status);
            }
        }

        // Filter by showroom (nếu có quyền lọc theo showroom)
        if ($request->filled('showroom_id') && \App\Helpers\PermissionHelper::canFilterByShowroom('sales')) {
            $query->where('showroom_id', $request->showroom_id);
        }

        // Filter by user (nhân viên bán) - nếu có quyền lọc theo nhân viên
        if ($request->filled('user_id') && \App\Helpers\PermissionHelper::canFilterByUser('sales')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range (nếu có quyền lọc theo ngày)
        $canFilterDate = Auth::user()->email === 'admin@example.com' ||
            (Auth::user()->role && Auth::user()->role->getModulePermissions('sales') && Auth::user()->role->getModulePermissions('sales')->can_filter_by_date);
        if ($canFilterDate) {
            if ($request->filled('from_date')) {
                $query->whereDate('sale_date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('sale_date', '<=', $request->to_date);
            }
        }

        // Filter by amount range
        if ($request->filled('min_amount')) {
            $query->where('total_vnd', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('total_vnd', '<=', $request->max_amount);
        }

        // Filter by debt status - Sử dụng accessor debt_usd và debt_vnd
        // Logic: Nhìn vào cột "Còn nợ (USD/VND)" - nếu > 0 thì có nợ, = 0 thì không nợ
        $filterDebt = $request->filled('has_debt') ? $request->has_debt : null;

        // Sort
        $sortBy = $request->get('sort_by', 'sale_date');
        $sortOrder = $request->get('sort_order', 'desc');

        if (in_array($sortBy, ['sale_date', 'total_vnd', 'paid_amount', 'debt_amount', 'invoice_code'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Nếu có filter theo công nợ, phải lọc bằng PHP vì accessor không thể dùng trong SQL
        if ($filterDebt !== null) {
            // Lấy tất cả dữ liệu và filter bằng accessor
            $allSales = $query->get();

            // Filter dựa trên accessor debt_usd và debt_vnd (giống hệt cột "Còn nợ" trên UI)
            $filteredSales = $allSales->filter(function ($sale) use ($filterDebt) {
                // Phiếu đã hủy: không có công nợ
                if ($sale->sale_status === 'cancelled') {
                    return $filterDebt == '0'; // Chỉ hiển thị khi lọc "Không công nợ"
                }

                // Tính công nợ thực tế từ accessor (giống view)
                $hasDebtUsd = $sale->debt_usd > 0.01;
                $hasDebtVnd = $sale->debt_vnd > 1;
                $hasAnyDebt = $hasDebtUsd || $hasDebtVnd;

                if ($filterDebt == '1') {
                    // Có công nợ: debt_usd > 0 HOẶC debt_vnd > 0
                    return $hasAnyDebt;
                } else {
                    // Không công nợ: debt_usd = 0 VÀ debt_vnd = 0
                    return !$hasAnyDebt;
                }
            });

            // Tạo custom paginator từ collection đã filter
            $page = $request->get('page', 1);
            $perPage = 10;
            $total = $filteredSales->count();
            $items = $filteredSales->forPage($page, $perPage);

            $sales = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $sales = $query->paginate(10)->withQueryString();
        }

        // Get filter options - chỉ lấy showroom được phép xem
        $showrooms = \App\Helpers\PermissionHelper::getAllowedShowrooms('sales');
        $users = User::all();

        // Kiểm tra các quyền để truyền vào view
        $canSearch = \App\Helpers\PermissionHelper::canSearch('sales');
        $canFilterByShowroom = \App\Helpers\PermissionHelper::canFilterByShowroom('sales');
        $canFilterByUser = \App\Helpers\PermissionHelper::canFilterByUser('sales');
        $canFilterByDate = Auth::user()->email === 'admin@example.com' ||
            (Auth::user()->role && Auth::user()->role->getModulePermissions('sales') && Auth::user()->role->getModulePermissions('sales')->can_filter_by_date);
        $canFilterByStatus = Auth::user()->email === 'admin@example.com' ||
            (Auth::user()->role && Auth::user()->role->getModulePermissions('sales') && Auth::user()->role->getModulePermissions('sales')->can_filter_by_status);

        // Debug: Log permission values
        \Log::info('Sales Index Permissions', [
            'user' => Auth::user()->email,
            'role' => Auth::user()->role ? Auth::user()->role->name : 'No Role',
            'canSearch' => $canSearch,
            'canFilterByShowroom' => $canFilterByShowroom,
            'canFilterByUser' => $canFilterByUser,
            'canFilterByDate' => $canFilterByDate,
            'canFilterByStatus' => $canFilterByStatus,
        ]);

        return view('sales.index', compact('sales', 'showrooms', 'users', 'canSearch', 'canFilterByShowroom', 'canFilterByUser', 'canFilterByDate', 'canFilterByStatus'));
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

        // Clean up empty strings and format for numeric fields
        if ($request->has('items')) {
            $items = $request->items;
            foreach ($items as $key => $item) {
                // Clean price_usd: empty string, "0", or formatted string → null or clean number
                if (isset($item['price_usd'])) {
                    $priceUsd = $item['price_usd'];
                    if ($priceUsd === '' || $priceUsd === '0' || $priceUsd === 0) {
                        $items[$key]['price_usd'] = null;
                    } else if (is_string($priceUsd)) {
                        // Remove formatting (commas, spaces) - keep dots for decimals
                        $items[$key]['price_usd'] = str_replace([',', ' '], '', $priceUsd);
                    }
                }

                // Clean price_vnd: empty string, "0", or formatted string → null or clean number
                if (isset($item['price_vnd'])) {
                    $priceVnd = $item['price_vnd'];
                    if ($priceVnd === '' || $priceVnd === '0' || $priceVnd === 0) {
                        $items[$key]['price_vnd'] = null;
                    } else if (is_string($priceVnd)) {
                        // Remove formatting (commas, dots, spaces)
                        $items[$key]['price_vnd'] = str_replace([',', '.', ' '], '', $priceVnd);
                    }
                }
            }
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email',
            'customer_address' => 'nullable|string',
            'showroom_id' => 'required|exists:showrooms,id',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.painting_id' => 'nullable|exists:paintings,id',
            'items.*.frame_id' => 'nullable|exists:frames,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.supply_id' => 'nullable|exists:supplies,id',
            'items.*.supply_length' => 'nullable|numeric|min:0',
            'items.*.currency' => 'required|in:USD,VND',
            'items.*.price_usd' => 'nullable|numeric|min:0',
            'items.*.price_vnd' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount_usd' => 'nullable|numeric|min:0',
            'items.*.discount_amount_vnd' => 'nullable',
            'shipping_fee_usd' => 'nullable|numeric|min:0',
            'shipping_fee_vnd' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount_usd' => 'nullable|numeric|min:0',
            'discount_amount_vnd' => 'nullable',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_usd' => 'nullable|numeric|min:0',
            'payment_vnd' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,card,other',
            'notes' => 'nullable|string',
        ], [
            'customer_id.exists' => 'Khách hàng không tồn tại',
            'customer_name.required_without' => 'Tên khách hàng là bắt buộc',
            'customer_name.string' => 'Tên khách hàng phải là chuỗi ký tự',
            'customer_name.max' => 'Tên khách hàng không được quá 255 ký tự',
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
            'exchange_rate.numeric' => 'Tỷ giá phải là số',
            'exchange_rate.min' => 'Tỷ giá phải lớn hơn hoặc bằng 0',
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
                // Kiểm tra xem có khách hàng trùng tên không
                $existingCustomer = Customer::where('name', $request->customer_name)->first();

                if ($existingCustomer && !$request->input('force_new_customer')) {
                    // Nếu tìm thấy KH cùng tên và không force tạo mới → sử dụng KH có sẵn
                    // Cập nhật thông tin nếu có thay đổi
                    if ($request->customer_phone || $request->customer_email || $request->customer_address) {
                        $existingCustomer->update([
                            'phone' => $request->customer_phone ?: $existingCustomer->phone,
                            'email' => $request->customer_email ?: $existingCustomer->email,
                            'address' => $request->customer_address ?: $existingCustomer->address,
                        ]);
                    }
                    $customer = $existingCustomer;
                } else {
                    // Tạo khách hàng mới (khi force_new_customer=true hoặc không tìm thấy khách hàng cùng tên)
                    $customer = Customer::create([
                        'name' => $request->customer_name,
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'address' => $request->customer_address,
                    ]);
                }
            }

            // Get default user
            $user = $this->getDefaultUser();

            // Lấy payment_usd và payment_vnd
            $paymentUsd = $request->payment_usd ?? 0;
            $paymentVnd = $request->payment_vnd ?? 0;

            // Xử lý exchange_rate - loại bỏ dấu phẩy, chấm
            $exchangeRate = $request->exchange_rate ?? 0;
            if (is_string($exchangeRate)) {
                $exchangeRate = (float) str_replace([',', '.', ' '], '', $exchangeRate);
            }

            // Tính paid_amount dựa vào loại payment
            // NOTE: paid_amount trong DB được dùng để tương thích và tính debt
            // Với logic mới: lưu riêng payment_usd và payment_vnd
            $paidAmount = $request->payment_amount ?? 0;  // Giá trị đã được tính từ frontend

            // Xử lý discount_amount cho Sale
            $discountAmountUsd = $request->discount_amount_usd ?? 0;
            $discountAmountVnd = $request->discount_amount_vnd ?? 0;
            if (is_string($discountAmountUsd)) {
                $discountAmountUsd = (float) str_replace([',', ' '], '', $discountAmountUsd);
            }
            if (is_string($discountAmountVnd)) {
                $discountAmountVnd = (float) str_replace([',', '.', ' '], '', $discountAmountVnd);
            }

            // Xử lý shipping_fee
            $shippingFeeUsd = $request->shipping_fee_usd ?? 0;
            $shippingFeeVnd = $request->shipping_fee_vnd ?? 0;
            if (is_string($shippingFeeUsd)) {
                $shippingFeeUsd = (float) str_replace([',', ' '], '', $shippingFeeUsd);
            }
            if (is_string($shippingFeeVnd)) {
                $shippingFeeVnd = (float) str_replace([',', '.', ' '], '', $shippingFeeVnd);
            }

            // Create sale
            $sale = Sale::create([
                'invoice_code' => $request->invoice_code ?: Sale::generateInvoiceCode($request->showroom_id),
                'customer_id' => $customer->id,
                'showroom_id' => $request->showroom_id,
                'user_id' => $user->id,
                'sale_date' => $request->sale_date,
                'exchange_rate' => number_format((float) $exchangeRate, 0, '', ''),
                'discount_percent' => $request->discount_percent ?? 0,
                'discount_amount_usd' => $discountAmountUsd,
                'discount_amount_vnd' => $discountAmountVnd,
                'shipping_fee_usd' => $shippingFeeUsd,
                'shipping_fee_vnd' => $shippingFeeVnd,
                'subtotal_usd' => 0,
                'subtotal_vnd' => 0,
                'total_usd' => 0,
                'total_vnd' => 0,
                'paid_amount' => $paidAmount,
                'payment_usd' => $paymentUsd,
                'payment_vnd' => $paymentVnd,
                'payment_method' => $request->payment_method ?? 'cash',
                'debt_amount' => 0,
                'payment_status' => 'unpaid',
                'notes' => $request->notes,
            ]);

            // Create sale items
            foreach ($request->items as $item) {
                // Xử lý discount_amount cho item
                $itemDiscountUsd = $item['discount_amount_usd'] ?? 0;
                $itemDiscountVnd = $item['discount_amount_vnd'] ?? 0;
                if (is_string($itemDiscountUsd)) {
                    $itemDiscountUsd = (float) str_replace([',', ' '], '', $itemDiscountUsd);
                }
                if (is_string($itemDiscountVnd)) {
                    $itemDiscountVnd = (float) str_replace([',', '.', ' '], '', $itemDiscountVnd);
                }

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'painting_id' => $item['painting_id'] ?? null,
                    'frame_id' => $item['frame_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'supply_id' => $item['supply_id'] ?? null,
                    'supply_length' => $item['supply_length'] ?? null,
                    'currency' => $item['currency'],
                    'price_usd' => $item['currency'] === 'USD' ? ($item['price_usd'] ?? 0) : 0,
                    'price_vnd' => $item['currency'] === 'VND' ? ($item['price_vnd'] ?? 0) : 0,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'discount_amount_usd' => $item['currency'] === 'USD' ? $itemDiscountUsd : 0,
                    'discount_amount_vnd' => $item['currency'] === 'VND' ? $itemDiscountVnd : 0,
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

            // Validation: Kiểm tra thanh toán không vượt quá tổng tiền
            // CHỈ áp dụng cho phiếu có CẢ USD VÀ VND
            if ($sale->total_usd > 0 && $sale->total_vnd > 0) {
                $tolerance = 0.01; // Sai số cho phép

                // Kiểm tra USD
                if ($paymentUsd > $sale->total_usd + $tolerance) {
                    throw new \Exception("Số tiền USD thanh toán (\${$paymentUsd}) vượt quá tổng USD (\${$sale->total_usd})");
                }

                // Kiểm tra VND
                if ($paymentVnd > $sale->total_vnd + 1) { // Tolerance 1 VND
                    throw new \Exception("Số tiền VND thanh toán (" . number_format((float) $paymentVnd) . "đ) vượt quá tổng VND (" . number_format((float) $sale->total_vnd) . "đ)");
                }
            }

            // KHÔNG tạo payment khi tạo phiếu pending
            // Payment sẽ được tạo khi duyệt phiếu (approve)

            // Create debt if there's remaining amount
            // KHÔNG tạo debt khi tạo phiếu pending
            // Debt sẽ được tạo khi duyệt phiếu (approve)

            // Log activity
            $this->activityLogger->logCreate(
                'sales',
                $sale,
                "Tạo phiếu bán hàng {$sale->invoice_code} cho khách hàng {$customer->name}"
            );

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
        $sale = Sale::with(['customer', 'showroom', 'user', 'saleItems.painting', 'saleItems.frame', 'saleItems.supply', 'payments', 'debt'])
            ->findOrFail($id);

        return view('sales.show', compact('sale'));
    }

    public function edit($id)
    {
        $sale = Sale::with(['saleItems.painting', 'saleItems.frame', 'saleItems.supply', 'customer', 'payments'])->findOrFail($id);

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
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email',
            'customer_address' => 'nullable|string',
            'showroom_id' => 'required|exists:showrooms,id',
            'sale_date' => 'required|date',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_usd' => 'nullable|numeric|min:0',
            'payment_vnd' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,card,other',
            'notes' => 'nullable|string',
        ];

        // Chỉ validate items nếu CHƯA có return
        if (!$hasReturns) {
            // Clean up empty strings and format for numeric fields
            if ($request->has('items')) {
                $items = $request->items;
                foreach ($items as $key => $item) {
                    // Clean price_usd: empty string, "0", or formatted string → null or clean number
                    if (isset($item['price_usd'])) {
                        $priceUsd = $item['price_usd'];
                        if ($priceUsd === '' || $priceUsd === '0' || $priceUsd === 0) {
                            $items[$key]['price_usd'] = null;
                        } else if (is_string($priceUsd)) {
                            // Remove formatting (commas, spaces)
                            $items[$key]['price_usd'] = str_replace([',', ' '], '', $priceUsd);
                        }
                    }

                    // Clean price_vnd: empty string, "0", or formatted string → null or clean number
                    if (isset($item['price_vnd'])) {
                        $priceVnd = $item['price_vnd'];
                        if ($priceVnd === '' || $priceVnd === '0' || $priceVnd === 0) {
                            $items[$key]['price_vnd'] = null;
                        } else if (is_string($priceVnd)) {
                            // Remove formatting (commas, dots, spaces)
                            $items[$key]['price_vnd'] = str_replace([',', '.', ' '], '', $priceVnd);
                        }
                    }
                }
                $request->merge(['items' => $items]);
            }

            $rules = array_merge($rules, [
                'items' => 'required|array|min:1',
                'items.*.painting_id' => 'nullable|exists:paintings,id',
                'items.*.frame_id' => 'nullable|exists:frames,id',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.supply_id' => 'nullable|exists:supplies,id',
                'items.*.supply_length' => 'nullable|numeric|min:0',
                'items.*.currency' => 'required|in:USD,VND',
                'items.*.price_usd' => 'nullable|numeric|min:0',
                'items.*.price_vnd' => 'nullable|numeric|min:0',
                'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
                'exchange_rate' => 'nullable|numeric|min:0',
                'discount_percent' => 'nullable|numeric|min:0|max:100',
            ]);
        }

        $validated = $request->validate($rules, [
            'customer_id.exists' => 'Khách hàng không tồn tại',
            'customer_name.required_without' => 'Tên khách hàng là bắt buộc',
            'customer_name.string' => 'Tên khách hàng phải là chuỗi ký tự',
            'customer_name.max' => 'Tên khách hàng không được quá 255 ký tự',
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
            'exchange_rate.numeric' => 'Tỷ giá phải là số',
            'exchange_rate.min' => 'Tỷ giá phải lớn hơn hoặc bằng 0',
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

                // Cho phép cập nhật thông tin khách hàng khi edit
                if ($customer) {
                    $updateData = [];

                    // Chỉ cập nhật các trường có giá trị mới
                    if ($request->filled('customer_name') && $request->customer_name !== $customer->name) {
                        $updateData['name'] = $request->customer_name;
                    }
                    if ($request->filled('customer_phone') && $request->customer_phone !== $customer->phone) {
                        $updateData['phone'] = $request->customer_phone;
                    }
                    if ($request->filled('customer_email') && $request->customer_email !== $customer->email) {
                        $updateData['email'] = $request->customer_email;
                    }
                    if ($request->filled('customer_address') && $request->customer_address !== $customer->address) {
                        $updateData['address'] = $request->customer_address;
                    }

                    // Cập nhật nếu có thay đổi
                    if (!empty($updateData)) {
                        $customer->update($updateData);
                    }
                }
            } else {
                // Kiểm tra xem có khách hàng trùng tên không
                $existingCustomer = Customer::where('name', $request->customer_name)->first();

                if ($existingCustomer && !$request->input('force_new_customer')) {
                    // Nếu tìm thấy KH cùng tên và không force tạo mới → sử dụng KH có sẵn
                    // Cập nhật thông tin nếu có thay đổi
                    if ($request->customer_phone || $request->customer_email || $request->customer_address) {
                        $existingCustomer->update([
                            'phone' => $request->customer_phone ?: $existingCustomer->phone,
                            'email' => $request->customer_email ?: $existingCustomer->email,
                            'address' => $request->customer_address ?: $existingCustomer->address,
                        ]);
                    }
                    $customer = $existingCustomer;
                } else {
                    // Tạo khách hàng mới
                    $customer = Customer::create([
                        'name' => $request->customer_name,
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'address' => $request->customer_address,
                    ]);
                }
            }

            // Get default user
            $user = $this->getDefaultUser();

            // Xử lý exchange_rate
            // CHÚ Ý: Chỉ cho phép thay đổi tỷ giá khi phiếu còn PENDING
            // Khi đã duyệt (completed), tỷ giá KHÔNG được thay đổi để đảm bảo tính toán chính xác
            $exchangeRate = $sale->exchange_rate; // Mặc định giữ nguyên tỷ giá cũ

            if ($sale->sale_status === 'pending' && $request->filled('exchange_rate')) {
                // Phiếu pending → Cho phép thay đổi tỷ giá
                $newExchangeRate = $request->exchange_rate;
                if (is_string($newExchangeRate)) {
                    $newExchangeRate = (float) str_replace([',', '.', ' '], '', $newExchangeRate);
                }
                $exchangeRate = $newExchangeRate;
            }
            // Nếu phiếu đã completed → Giữ nguyên exchange_rate cũ
            if ($sale->exchange_rate != $exchangeRate) {
                $sale->update([
                    'exchange_rate' => number_format((float) $exchangeRate, 0, '', '')
                ]);
            }

            // Xử lý discount_amount cho Sale (khi update)
            $discountAmountUsd = $request->discount_amount_usd ?? $sale->discount_amount_usd ?? 0;
            $discountAmountVnd = $request->discount_amount_vnd ?? $sale->discount_amount_vnd ?? 0;
            if (is_string($discountAmountUsd)) {
                $discountAmountUsd = (float) str_replace([',', ' '], '', $discountAmountUsd);
            }
            if (is_string($discountAmountVnd)) {
                $discountAmountVnd = (float) str_replace([',', '.', ' '], '', $discountAmountVnd);
            }

            // Xử lý shipping_fee (khi update)
            $shippingFeeUsd = $request->shipping_fee_usd ?? $sale->shipping_fee_usd ?? 0;
            $shippingFeeVnd = $request->shipping_fee_vnd ?? $sale->shipping_fee_vnd ?? 0;
            if (is_string($shippingFeeUsd)) {
                $shippingFeeUsd = (float) str_replace([',', ' '], '', $shippingFeeUsd);
            }
            if (is_string($shippingFeeVnd)) {
                $shippingFeeVnd = (float) str_replace([',', '.', ' '], '', $shippingFeeVnd);
            }

            // Update sale
            $sale->update([
                'customer_id' => $customer->id,
                'showroom_id' => $request->showroom_id,
                'user_id' => $user->id,
                'sale_date' => $request->sale_date,
                'exchange_rate' => $exchangeRate, // Chỉ thay đổi nếu pending
                'discount_percent' => $request->discount_percent ?? 0,
                'discount_amount_usd' => $discountAmountUsd,
                'discount_amount_vnd' => $discountAmountVnd,
                'shipping_fee_usd' => $shippingFeeUsd,
                'shipping_fee_vnd' => $shippingFeeVnd,
                'payment_method' => $request->payment_method ?? $sale->payment_method,
                'notes' => $request->notes,
                'invoice_code' => $request->invoice_code ?: $sale->invoice_code,
            ]);

            // Refresh để đảm bảo dữ liệu mới được load
            $sale->refresh();

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

                        // Hoàn trả status khung về available
                        if ($oldItem->frame_id) {
                            $frame = \App\Models\Frame::find($oldItem->frame_id);
                            if ($frame) {
                                $frame->markAsAvailable();
                            }
                        }
                    }
                }

                // Delete existing sale items
                $sale->saleItems()->delete();

                // Create new sale items
                foreach ($request->items as $item) {
                    // Xử lý discount_amount cho item
                    $itemDiscountUsd = $item['discount_amount_usd'] ?? 0;
                    $itemDiscountVnd = $item['discount_amount_vnd'] ?? 0;
                    if (is_string($itemDiscountUsd)) {
                        $itemDiscountUsd = (float) str_replace([',', ' '], '', $itemDiscountUsd);
                    }
                    if (is_string($itemDiscountVnd)) {
                        $itemDiscountVnd = (float) str_replace([',', '.', ' '], '', $itemDiscountVnd);
                    }

                    $saleItem = SaleItem::create([
                        'sale_id' => $sale->id,
                        'painting_id' => $item['painting_id'] ?? null,
                        'frame_id' => $item['frame_id'] ?? null,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'supply_id' => $item['supply_id'] ?? null,
                        'supply_length' => $item['supply_length'] ?? null,
                        'currency' => $item['currency'],
                        'price_usd' => $item['currency'] === 'USD' ? ($item['price_usd'] ?? 0) : 0,
                        'price_vnd' => $item['currency'] === 'VND' ? ($item['price_vnd'] ?? 0) : 0,
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'discount_amount_usd' => $item['currency'] === 'USD' ? $itemDiscountUsd : 0,
                        'discount_amount_vnd' => $item['currency'] === 'VND' ? $itemDiscountVnd : 0,
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

                        // Đánh dấu khung mới là đã bán
                        if ($saleItem->frame_id) {
                            $frame = \App\Models\Frame::find($saleItem->frame_id);
                            if ($frame) {
                                if (!$frame->isAvailable()) {
                                    throw new \Exception("Khung {$frame->name} đã được bán");
                                }
                                $frame->markAsSold();
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
                    $paymentUsd = $request->payment_usd ?? 0;
                    $paymentVnd = $request->payment_vnd ?? 0;

                    // Xác định loại hóa đơn
                    $hasUsdTotal = $sale->total_usd > 0;
                    $hasVndTotal = $sale->total_vnd > 0;

                    // Lấy tỷ giá - CHỈ lưu khi thanh toán CHÉO
                    $paymentExchangeRate = null;

                    // Trường hợp 1: Hóa đơn USD, trả VND (USD-VND)
                    if ($hasUsdTotal && !$hasVndTotal && $paymentVnd > 0) {
                        $paymentExchangeRate = $request->exchange_rate ?? $sale->exchange_rate;
                        if (is_string($paymentExchangeRate)) {
                            $paymentExchangeRate = (float) str_replace([',', '.', ' '], '', $paymentExchangeRate);
                        }
                    }

                    // Trường hợp 2: Hóa đơn VND, trả USD (VND-USD)
                    if ($hasVndTotal && !$hasUsdTotal && $paymentUsd > 0) {
                        $paymentExchangeRate = $request->exchange_rate ?? $sale->exchange_rate;
                        if (is_string($paymentExchangeRate)) {
                            $paymentExchangeRate = (float) str_replace([',', '.', ' '], '', $paymentExchangeRate);
                        }
                    }

                    // Trường hợp 3: Hóa đơn Mixed (USD+VND) - KHÔNG lưu tỷ giá
                    // payment_exchange_rate = null

                    Payment::create([
                        'sale_id' => $sale->id,
                        'amount' => $request->payment_amount,
                        'payment_usd' => $paymentUsd,
                        'payment_vnd' => $paymentVnd,
                        'payment_exchange_rate' => $paymentExchangeRate,
                        'payment_method' => $request->payment_method ?? 'cash',
                        'transaction_type' => 'sale_payment',
                        'payment_date' => now(),
                        'notes' => 'Trả nợ thêm',
                        'created_by' => $user->id,
                    ]);
                } else {
                    // Phiếu pending - cập nhật paid_amount trong sale (chưa tạo payment)
                    $sale->paid_amount = $request->payment_amount;
                    $sale->payment_usd = $request->payment_usd ?? 0;
                    $sale->payment_vnd = $request->payment_vnd ?? 0;

                    // Tính debt_amount theo logic mới (sử dụng accessor)
                    // Accessor sẽ tự động tính đúng theo loại hóa đơn
                    $debtUsd = $sale->debt_usd;
                    $debtVnd = $sale->debt_vnd;

                    // Lưu debt_amount (để tương thích với code cũ)
                    // Ưu tiên VND nếu có, không thì dùng USD quy đổi
                    if ($debtVnd > 0) {
                        $sale->debt_amount = $debtVnd;
                    } else if ($debtUsd > 0) {
                        $sale->debt_amount = $debtUsd * $sale->exchange_rate;
                    } else {
                        $sale->debt_amount = 0;
                    }

                    $sale->save();
                }
            }

            // Update debt if there's remaining amount
            // Update debt ONLY if sale is completed
            if ($sale->isCompleted()) {
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
                } elseif ($existingDebt) {
                    // If debt becomes 0, update existing record
                    $existingDebt->update([
                        'customer_id' => $customer->id,
                        'total_amount' => $sale->total_vnd,
                        'paid_amount' => $sale->paid_amount,
                        'debt_amount' => 0,
                        'status' => 'paid'
                    ]);
                }
            }

            // Log activity
            $this->activityLogger->logUpdate(
                'sales',
                $sale,
                [],
                "Cập nhật phiếu bán hàng {$sale->invoice_code}"
            );

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

        // Chỉ cho xóa phiếu chưa duyệt (pending)
        // Phiếu đã duyệt (completed) không được xóa
        if ($sale->sale_status === 'completed') {
            Log::info('Cannot delete completed sale');
            return back()->with('error', 'Không thể xóa hóa đơn đã duyệt');
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

                    // Hoàn trả status khung về available
                    if ($item->frame_id) {
                        $frame = \App\Models\Frame::find($item->frame_id);
                        if ($frame) {
                            $frame->markAsAvailable();
                        }
                    }
                }
            }

            // Log activity before deletion
            $this->activityLogger->logDelete(
                'sales',
                $sale,
                [
                    'invoice_code' => $sale->invoice_code,
                    'customer' => $sale->customer->name ?? 'N/A',
                    'total_vnd' => $sale->total_vnd,
                    'total_usd' => $sale->total_usd,
                ],
                "Xóa phiếu bán hàng {$sale->invoice_code}"
            );

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
        $sale = Sale::with(['customer', 'showroom', 'saleItems.painting', 'saleItems.frame', 'saleItems.supply'])
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

        // LOGIC MỚI: Tính tổng công nợ riêng USD và VND
        $sales = $customer->sales()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('sale_status', 'completed')
            ->get();

        $totalDebtUsd = $sales->sum(function ($sale) {
            return $sale->debt_usd ?? 0;
        });

        $totalDebtVnd = $sales->sum(function ($sale) {
            return $sale->debt_vnd ?? 0;
        });

        // Backward compatibility: total_debt = VND (cũ)
        $totalDebt = $sales->sum('debt_amount');

        return response()->json([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'total_debt' => $totalDebt,
            'total_debt_usd' => $totalDebtUsd,
            'total_debt_vnd' => $totalDebtVnd
        ]);
    }

    public function searchPaintings(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([]);
        }

        $paintings = Painting::where(function ($q) use ($query) {
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

    public function searchFrames(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([]);
        }

        $frames = \App\Models\Frame::where('name', 'like', "%{$query}%")
            ->where('status', 'available')
            ->limit(10)
            ->get(['id', 'name', 'cost_price']);

        return response()->json($frames);
    }

    // API: Search suggestions cho trang index
    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        $suggestions = [];

        // Tìm theo mã hóa đơn (theo năm đang chọn)
        $selectedYear = session('selected_year', date('Y'));
        $invoices = Sale::where('invoice_code', 'like', "%{$query}%")
            ->where('year', $selectedYear)
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
        $customers = Customer::where(function ($q) use ($query) {
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

        // KIỂM TRA TỒN KHO TRƯỚC KHI DUYỆT
        $stockErrors = [];
        foreach ($sale->saleItems as $saleItem) {
            // Kiểm tra tranh
            if ($saleItem->painting_id) {
                $painting = Painting::find($saleItem->painting_id);
                if (!$painting) {
                    $stockErrors[] = "Tranh (ID: {$saleItem->painting_id}) đã bị xóa khỏi kho";
                } elseif ($painting->status !== 'in_stock') {
                    $stockErrors[] = "Tranh {$painting->code} - {$painting->name} đã được bán";
                } elseif ($painting->quantity < $saleItem->quantity) {
                    $stockErrors[] = "Tranh {$painting->code} không đủ số lượng (cần: {$saleItem->quantity}, còn: {$painting->quantity})";
                }
            }

            // Kiểm tra vật tư
            if ($saleItem->supply_id && $saleItem->supply_length) {
                $supply = Supply::find($saleItem->supply_id);
                $totalRequired = $saleItem->supply_length * $saleItem->quantity;

                if (!$supply) {
                    $stockErrors[] = "Vật tư (ID: {$saleItem->supply_id}) đã bị xóa khỏi kho";
                } else {
                    // Tính tổng chiều dài còn lại
                    $availableLength = $supply->tree_count * $supply->quantity;
                    if ($availableLength < $totalRequired) {
                        $stockErrors[] = "Vật tư {$supply->code} không đủ (cần: {$totalRequired}{$supply->unit}, còn: {$availableLength}{$supply->unit})";
                    }
                }
            }

            // Kiểm tra khung
            if ($saleItem->frame_id) {
                $frame = \App\Models\Frame::find($saleItem->frame_id);
                if (!$frame) {
                    $stockErrors[] = "Khung (ID: {$saleItem->frame_id}) đã bị xóa";
                } elseif (!$frame->isAvailable()) {
                    $stockErrors[] = "Khung {$frame->name} đã được bán";
                }
            }
        }

        // Nếu có lỗi tồn kho, không cho duyệt
        if (!empty($stockErrors)) {
            return back()->with('error', 'Không thể duyệt phiếu do lỗi tồn kho: ' . implode('; ', $stockErrors));
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

                // Process frame status
                if ($saleItem->frame_id) {
                    $frame = \App\Models\Frame::find($saleItem->frame_id);
                    if ($frame) {
                        if (!$frame->isAvailable()) {
                            throw new \Exception("Khung {$frame->name} đã được bán");
                        }
                        $frame->markAsSold();
                    }
                }
            }

            // Update sale status to completed
            $sale->update(['sale_status' => 'completed']);

            // Tạo payment record từ paid_amount ban đầu (nếu có)
            // CHÚ Ý: Kiểm tra payment_usd hoặc payment_vnd thay vì paid_amount
            // Vì paid_amount có thể được tính sai khi chỉ trả USD
            $hasInitialPayment = ($sale->payment_usd ?? 0) > 0 || ($sale->payment_vnd ?? 0) > 0;

            if ($hasInitialPayment) {
                // Xác định payment_exchange_rate: CHỈ lưu khi có thanh toán VND
                $paymentExchangeRate = null;
                if (($sale->payment_vnd ?? 0) > 0) {
                    // Có thanh toán VND → Lưu tỷ giá tại thời điểm này
                    $paymentExchangeRate = $sale->exchange_rate;
                }
                // Nếu chỉ trả USD → payment_exchange_rate = null

                Payment::create([
                    'sale_id' => $sale->id,
                    'amount' => $sale->paid_amount,
                    'payment_usd' => $sale->payment_usd ?? 0,
                    'payment_vnd' => $sale->payment_vnd ?? 0,
                    'payment_exchange_rate' => $paymentExchangeRate, // Lưu tỷ giá tại thời điểm thanh toán
                    'payment_method' => $sale->payment_method ?? 'cash', // Lấy từ sale
                    'transaction_type' => 'sale_payment',
                    'payment_date' => now(),
                    'notes' => 'Thanh toán ban đầu khi duyệt phiếu',
                    'created_by' => $user->id,
                ]);
            }

            // Create debt if there's remaining amount (check both USD and VND)
            $debtUsd = $sale->total_usd - ($sale->paid_usd ?? 0);
            $debtVnd = $sale->total_vnd - ($sale->paid_vnd ?? 0);
            $hasDebtUsd = $debtUsd > 0.01;
            $hasDebtVnd = $debtVnd > 1000;

            if ($hasDebtUsd || $hasDebtVnd || $sale->debt_amount > 0) {
                // Tính debt_amount (VND) nếu chưa có
                $debtAmount = $sale->debt_amount;
                if ($debtAmount <= 0) {
                    $debtAmount = $debtVnd + ($debtUsd * ($sale->exchange_rate ?: 1));
                }

                Debt::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    // USD fields
                    'total_usd' => $sale->total_usd ?? 0,
                    'paid_usd' => $sale->paid_usd ?? 0,
                    'debt_usd' => max(0, $debtUsd),
                    'exchange_rate' => $sale->exchange_rate ?? 0,
                    // VND fields
                    'total_amount' => $sale->total_vnd ?? 0,
                    'paid_amount' => $sale->paid_vnd ?? 0,
                    'debt_amount' => max(0, $debtVnd),
                    'due_date' => now()->addDays(30),
                    'status' => 'unpaid',
                ]);
            }

            // Log approval activity
            $this->activityLogger->logApprove(
                'sales',
                $sale,
                null,
                "Duyệt phiếu bán hàng {$sale->invoice_code}"
            );

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

        // Chỉ cho hủy khi phiếu chờ duyệt (pending)
        // Phiếu đã duyệt (completed) không được hủy
        if (!$sale->isPending()) {
            return back()->with('error', 'Chỉ có thể hủy phiếu đang chờ duyệt');
        }

        DB::beginTransaction();
        try {
            // Update sale status to cancelled
            $sale->update(['sale_status' => 'cancelled']);

            // Log cancellation activity
            $this->activityLogger->logCancel(
                'sales',
                $sale,
                null,
                "Hủy phiếu bán hàng {$sale->invoice_code}"
            );

            DB::commit();

            return back()->with('success', 'Đã hủy phiếu bán hàng ' . $sale->invoice_code);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cancel sale error: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * API: Generate invoice code for given showroom
     */
    public function generateInvoiceCodeApi(Request $request)
    {
        $showroomId = $request->query('showroom_id');

        if (!$showroomId) {
            return response()->json([
                'success' => false,
                'message' => 'Showroom ID is required'
            ], 400);
        }

        try {
            $invoiceCode = Sale::generateInvoiceCode($showroomId);

            return response()->json([
                'success' => true,
                'invoice_code' => $invoiceCode
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $user = auth()->user();

        // Get filter parameters (same as index)
        $query = Sale::with(['customer', 'showroom', 'user']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('showroom_id')) {
            $query->where('showroom_id', $request->showroom_id);
        }

        if ($request->filled('sale_status')) {
            $query->where('sale_status', $request->sale_status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }

        $format = $request->export; // 'excel' or 'pdf'
        $exportType = $request->export_type; // 'all' or 'separate'

        if ($exportType === 'separate' && !$request->filled('showroom_id')) {
            // Export each showroom separately
            return $this->exportByShowroom($query, $format);
        } else {
            // Export all in one file
            $sales = $query->orderBy('sale_date', 'desc')->get();
            $filename = 'danh-sach-ban-hang-' . date('Y-m-d-His');

            if ($format === 'excel') {
                return $this->exportExcel($sales, $filename);
            } else {
                return $this->exportPDF($sales, $filename);
            }
        }
    }

    private function exportByShowroom($query, $format)
    {
        $showrooms = \App\Models\Showroom::all();
        $zip = new \ZipArchive();
        $zipFilename = storage_path('app/temp/sales-by-showroom-' . date('Y-m-d-His') . '.zip');

        // Create temp directory if not exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $filesAdded = 0;

        if ($zip->open($zipFilename, \ZipArchive::CREATE) === TRUE) {
            foreach ($showrooms as $showroom) {
                $showroomSales = (clone $query)->where('showroom_id', $showroom->id)->orderBy('sale_date', 'desc')->get();

                if ($showroomSales->count() > 0) {
                    $filename = 'ban-hang-' . \Str::slug($showroom->name) . '-' . date('Y-m-d');

                    try {
                        if ($format === 'excel') {
                            $export = new \App\Exports\SalesExport($showroomSales, $showroom->name);
                            $filepath = storage_path('app/temp/' . $filename . '.xlsx');

                            // Tạo file Excel bằng cách lưu trực tiếp
                            $writer = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
                            file_put_contents($filepath, $writer);

                            // Kiểm tra file có tồn tại không
                            if (file_exists($filepath) && filesize($filepath) > 0) {
                                $zip->addFile($filepath, $filename . '.xlsx');
                                $filesAdded++;
                            } else {
                                Log::warning("Excel file not created or empty: " . $filepath);
                            }
                        } else {
                            $pdf = Pdf::loadView('sales.export-pdf', [
                                'sales' => $showroomSales,
                                'title' => 'Danh sách bán hàng - ' . $showroom->name
                            ]);
                            $filepath = storage_path('app/temp/' . $filename . '.pdf');
                            $pdf->save($filepath);

                            if (file_exists($filepath)) {
                                $zip->addFile($filepath, $filename . '.pdf');
                                $filesAdded++;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Error exporting showroom ' . $showroom->name . ': ' . $e->getMessage());
                        continue;
                    }
                }
            }
            $zip->close();

            // Kiểm tra có file nào được thêm không
            if ($filesAdded === 0) {
                // Xóa file zip rỗng
                if (file_exists($zipFilename)) {
                    unlink($zipFilename);
                }
                return back()->with('error', 'Không có dữ liệu để xuất!');
            }

            // Kiểm tra file zip có tồn tại không
            if (!file_exists($zipFilename)) {
                return back()->with('error', 'Không thể tạo file zip!');
            }

            // Xóa các file tạm trong thư mục temp (trừ file zip)
            $tempFiles = glob(storage_path('app/temp/*'));
            foreach ($tempFiles as $file) {
                if (is_file($file) && $file !== $zipFilename) {
                    unlink($file);
                }
            }

            return response()->download($zipFilename)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Không thể tạo file zip');
    }

    private function exportExcel($sales, $filename)
    {
        $export = new \App\Exports\SalesExport($sales);
        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename . '.xlsx');
    }

    private function exportPDF($sales, $filename)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.export-pdf', [
            'sales' => $sales,
            'title' => 'Danh sách bán hàng'
        ]);

        return $pdf->download($filename . '.pdf');
    }
}
