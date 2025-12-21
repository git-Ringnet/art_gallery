<?php

namespace App\Http\Controllers;

use App\Models\ReturnModel;
use App\Models\ReturnItem;
use App\Models\ExchangeItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Painting;
use App\Models\Supply;
use App\Models\Payment;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnModel::with(['sale', 'customer', 'processedBy', 'items.painting', 'items.supply']);

        // Lọc theo năm đang chọn
        $selectedYear = session('selected_year', date('Y'));
        $query->where('year', $selectedYear);

        // Áp dụng phạm vi dữ liệu - custom logic cho Returns
        if (Auth::check() && Auth::user()->email !== 'admin@example.com') {
            $role = Auth::user()->role;
            if ($role) {
                $dataScope = $role->getDataScope('returns');
                
                switch ($dataScope) {
                    case 'own':
                        // Chỉ xem return mà chính mình xử lý (processed_by)
                        $query->where('processed_by', Auth::id());
                        break;
                    
                    case 'showroom':
                        // Xem return của các sale thuộc showroom được phép
                        $allowedShowrooms = $role->getAllowedShowrooms('returns');
                        if ($allowedShowrooms && is_array($allowedShowrooms) && count($allowedShowrooms) > 0) {
                            $query->whereHas('sale', function($q) use ($allowedShowrooms) {
                                $q->whereIn('showroom_id', $allowedShowrooms);
                            });
                        }
                        break;
                    
                    case 'all':
                        // Xem tất cả - không filter
                        break;
                    
                    case 'none':
                    default:
                        // Không có quyền
                        $query->whereRaw('1 = 0');
                        break;
                }
            }
        }

        // Search by return code, invoice code, or customer (nếu có quyền)
        if ($request->filled('search') && \App\Helpers\PermissionHelper::canSearch('returns')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('return_code', 'like', "%{$search}%")
                  ->orWhereHas('sale', function($q) use ($search) {
                      $q->where('invoice_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by showroom (nếu có quyền)
        if ($request->filled('showroom_id') && \App\Helpers\PermissionHelper::canFilterByShowroom('returns')) {
            $query->whereHas('sale', function($q) use ($request) {
                $q->where('showroom_id', $request->showroom_id);
            });
        }

        // Filter by user (nếu có quyền)
        if ($request->filled('user_id') && \App\Helpers\PermissionHelper::canFilterByUser('returns')) {
            $query->where('processed_by', $request->user_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status (nếu có quyền)
        if ($request->filled('status')) {
            $canFilterStatus = Auth::user()->email === 'admin@example.com' || 
                              (Auth::user()->role && Auth::user()->role->getModulePermissions('returns') && 
                               Auth::user()->role->getModulePermissions('returns')->can_filter_by_status);
            if ($canFilterStatus) {
                $query->where('status', $request->status);
            }
        }

        // Filter by date range (nếu có quyền)
        $canFilterDate = Auth::user()->email === 'admin@example.com' || 
                        (Auth::user()->role && Auth::user()->role->getModulePermissions('returns') && 
                         Auth::user()->role->getModulePermissions('returns')->can_filter_by_date);
        if ($canFilterDate) {
            if ($request->filled('from_date')) {
                $query->whereDate('return_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('return_date', '<=', $request->to_date);
            }
        }

        $returns = $query->orderBy('created_at', 'desc')->paginate(10)->appends($request->query());

        // Lấy showroom được phép
        $showrooms = \App\Helpers\PermissionHelper::getAllowedShowrooms('returns');
        $users = \App\Models\User::all();

        // Truyền quyền vào view
        $canSearch = \App\Helpers\PermissionHelper::canSearch('returns');
        $canFilterByShowroom = \App\Helpers\PermissionHelper::canFilterByShowroom('returns');
        $canFilterByUser = \App\Helpers\PermissionHelper::canFilterByUser('returns');
        $canFilterByDate = $canFilterDate;
        $canFilterByStatus = Auth::user()->email === 'admin@example.com' || 
                            (Auth::user()->role && Auth::user()->role->getModulePermissions('returns') && 
                             Auth::user()->role->getModulePermissions('returns')->can_filter_by_status);

        return view('returns.index', compact('returns', 'showrooms', 'users', 'canSearch', 'canFilterByShowroom', 'canFilterByUser', 'canFilterByDate', 'canFilterByStatus'));
    }

    public function create()
    {
        return view('returns.create');
    }

    public function searchInvoice(Request $request)
    {
        $request->validate([
            'invoice_code' => 'required|string',
        ]);

        $sale = Sale::with(['items.painting', 'items.frame', 'items.supply', 'customer'])
            ->where('invoice_code', $request->invoice_code)
            ->first();

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy hóa đơn'
            ], 404);
        }

        // Chỉ cho phép trả/đổi hàng từ phiếu đã hoàn thành
        if ($sale->sale_status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể trả/đổi hàng từ phiếu bán đã được duyệt và hoàn thành'
            ], 400);
        }

        // CHẶN: Không cho phép trả/đổi hàng nếu hóa đơn có bán khung
        $hasFrame = $sale->items->contains(function($item) {
            return !empty($item->frame_id);
        });

        if ($hasFrame) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo phiếu trả/đổi hàng cho hóa đơn có bán khung. Vui lòng liên hệ quản lý để xử lý.'
            ], 400);
        }

        // Get returned quantities for each item (only approved/completed returns)
        $returnedQuantities = [];
        foreach ($sale->items as $item) {
            $returned = ReturnItem::where('sale_item_id', $item->id)
                ->whereHas('return', function($q) {
                    $q->whereIn('status', ['approved', 'completed']);
                })
                ->sum('quantity');
            $returnedQuantities[$item->id] = $returned;
            
            // Add item name and image for display
            if ($item->painting_id) {
                $item->item_name = $item->painting->name ?? 'N/A';
                $item->painting_image = $item->painting->image ?? null;
            } elseif ($item->frame_id) {
                $item->item_name = $item->frame->name ?? 'N/A';
                $item->painting_image = null;
            } else {
                $item->item_name = $item->supply->name ?? 'N/A';
                $item->painting_image = null;
            }
            
            // Calculate unit price after applying discounts (CẢ USD VÀ VND)
            $unitPriceUsd = $item->price_usd;
            $unitPriceVnd = $item->price_vnd;
            
            // Apply item-level discount if exists
            if ($item->discount_percent > 0) {
                $unitPriceUsd = $unitPriceUsd * (1 - $item->discount_percent / 100);
                $unitPriceVnd = $unitPriceVnd * (1 - $item->discount_percent / 100);
            }
            
            // Apply sale-level discount if exists
            if ($sale->discount_percent > 0) {
                $unitPriceUsd = $unitPriceUsd * (1 - $sale->discount_percent / 100);
                $unitPriceVnd = $unitPriceVnd * (1 - $sale->discount_percent / 100);
            }
            
            $item->unit_price = $unitPriceVnd;
            $item->unit_price_usd = $unitPriceUsd;
            $item->currency = $item->currency;
        }

        // Sử dụng accessor từ Sale model (logic đúng)
        $saleData = [
            'id' => $sale->id,
            'invoice_code' => $sale->invoice_code,
            'customer' => $sale->customer,
            'sale_date' => $sale->sale_date,
            'exchange_rate' => $sale->exchange_rate,
            'discount_percent' => $sale->discount_percent,
            'items' => $sale->items,
            'total_usd' => $sale->total_usd,
            'total_vnd' => $sale->total_vnd,
            'paid_usd' => $sale->paid_usd,
            'paid_vnd' => $sale->paid_vnd,
            'debt_usd' => $sale->debt_usd,
            'debt_vnd' => $sale->debt_vnd,
            'is_usd_invoice' => $sale->saleItems->where('currency', 'USD')->count() > 0 && $sale->saleItems->where('currency', 'VND')->count() == 0,
            'is_vnd_invoice' => $sale->saleItems->where('currency', 'VND')->count() > 0 && $sale->saleItems->where('currency', 'USD')->count() == 0,
            'is_mixed_invoice' => $sale->saleItems->where('currency', 'USD')->count() > 0 && $sale->saleItems->where('currency', 'VND')->count() > 0,
        ];

        return response()->json([
            'success' => true,
            'sale' => $saleData,
            'returned_quantities' => $returnedQuantities
        ]);
    }

    public function store(Request $request)
    {
        // Convert formatted payment inputs to numbers
        $paymentUsd = $request->filled('payment_usd') ? (float)str_replace(',', '', $request->payment_usd) : 0;
        $paymentVnd = $request->filled('payment_vnd') ? (float)str_replace(',', '', $request->payment_vnd) : 0;
        $paymentExchangeRate = $request->filled('payment_exchange_rate') ? (float)str_replace(',', '', $request->payment_exchange_rate) : 25000;
        
        // Calculate total payment in VND for validation
        $totalPaymentVnd = ($paymentUsd * $paymentExchangeRate) + $paymentVnd;
        
        if ($totalPaymentVnd > 0) {
            $request->merge([
                'payment_amount' => $totalPaymentVnd,
                'payment_usd' => $paymentUsd,
                'payment_vnd' => $paymentVnd,
                'payment_exchange_rate' => $paymentExchangeRate,
            ]);
        }
        
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'type' => 'nullable|in:return,exchange',
            'return_date' => 'required|date',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|integer|min:0',
            'items.*.reason' => 'nullable|string',
            'exchange_items' => 'nullable|array',
            'exchange_items.*.item_type' => 'nullable|in:painting,supply',
            'exchange_items.*.item_id' => 'nullable|integer',
            'exchange_items.*.quantity' => 'nullable|integer|min:1',
            'exchange_items.*.unit_price' => 'nullable|numeric',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,card',
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::findOrFail($request->sale_id);
            
            // Validate that at least one item has quantity > 0
            $hasValidItems = false;
            foreach ($request->items as $itemData) {
                if (isset($itemData['quantity']) && $itemData['quantity'] > 0) {
                    $hasValidItems = true;
                    break;
                }
            }
            
            if (!$hasValidItems) {
                throw new \Exception('Phải có ít nhất một sản phẩm với số lượng trả > 0');
            }
            
            // Calculate total value of returned items (USD + VND)
            $totalReturnValueUsd = 0;
            $totalReturnValueVnd = 0;
            $returnItems = [];

            foreach ($request->items as $itemData) {
                if (!isset($itemData['quantity']) || $itemData['quantity'] <= 0) {
                    continue; // Skip items with 0 quantity
                }
                
                $saleItem = SaleItem::findOrFail($itemData['sale_item_id']);
                
                // Check if quantity is valid (only approved/completed returns)
                $alreadyReturned = ReturnItem::where('sale_item_id', $saleItem->id)
                    ->whereHas('return', function($q) {
                        $q->whereIn('status', ['approved', 'completed']);
                    })
                    ->sum('quantity');
                
                $availableQty = $saleItem->quantity - $alreadyReturned;
                
                if ($itemData['quantity'] > $availableQty) {
                    $itemName = $saleItem->painting_id ? ($saleItem->painting->name ?? 'N/A') : ($saleItem->supply->name ?? 'N/A');
                    throw new \Exception("Số lượng trả vượt quá số lượng có thể trả cho sản phẩm {$itemName}");
                }

                // Determine item type and id
                if ($saleItem->painting_id) {
                    $itemType = 'painting';
                    $itemId = $saleItem->painting_id;
                } elseif ($saleItem->frame_id) {
                    $itemType = 'frame';
                    $itemId = $saleItem->frame_id;
                } else {
                    $itemType = 'supply';
                    $itemId = $saleItem->supply_id;
                }
                
                // Calculate unit price after applying discounts (CẢ USD VÀ VND)
                $currency = $saleItem->currency;
                $exchangeRate = ($sale->exchange_rate && $sale->exchange_rate > 0) ? $sale->exchange_rate : 25000;
                
                // Lấy giá gốc dựa trên currency - KHÔNG QUY ĐỔI
                if ($currency === 'USD') {
                    $unitPriceUsd = $saleItem->price_usd;
                    $unitPriceVnd = 0; // Không tính VND cho USD items
                } else {
                    $unitPriceVnd = $saleItem->price_vnd;
                    $unitPriceUsd = 0; // Không tính USD cho VND items
                }
                
                // Apply item-level discount if exists
                if ($saleItem->discount_percent > 0) {
                    if ($currency === 'USD') {
                        $unitPriceUsd = $unitPriceUsd * (1 - $saleItem->discount_percent / 100);
                    } else {
                        $unitPriceVnd = $unitPriceVnd * (1 - $saleItem->discount_percent / 100);
                    }
                }
                
                // Apply sale-level discount if exists
                if ($sale->discount_percent > 0) {
                    if ($currency === 'USD') {
                        $unitPriceUsd = $unitPriceUsd * (1 - $sale->discount_percent / 100);
                    } else {
                        $unitPriceVnd = $unitPriceVnd * (1 - $sale->discount_percent / 100);
                    }
                }
                
                $subtotalUsd = $itemData['quantity'] * $unitPriceUsd;
                $subtotalVnd = $itemData['quantity'] * $unitPriceVnd;
                
                // Chỉ cộng vào tổng theo currency gốc
                if ($currency === 'USD') {
                    $totalReturnValueUsd += $subtotalUsd;
                } else {
                    $totalReturnValueVnd += $subtotalVnd;
                }

                $returnItems[] = [
                    'sale_item_id' => $saleItem->id,
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'quantity' => $itemData['quantity'],
                    'supply_id' => $saleItem->supply_id ?? null,
                    'supply_length' => $saleItem->supply_length ?? 0,
                    'unit_price' => $unitPriceVnd,
                    'unit_price_usd' => $unitPriceUsd,
                    'subtotal' => $subtotalVnd,
                    'subtotal_usd' => $subtotalUsd,
                    'currency' => $currency,
                    'reason' => $itemData['reason'] ?? null,
                ];
            }

            // Get type from request
            $type = $request->input('type', 'return');
            
            // Tính số tiền hoàn lại = MIN(Đã trả, Giá trị SP trả)
            // Logic: Chỉ hoàn lại số tiền thực tế đã trả, không vượt quá giá trị SP
            $totalRefundUsd = min($sale->paid_usd, $totalReturnValueUsd);
            $totalRefundVnd = min($sale->paid_vnd, $totalReturnValueVnd);
            
            $exchangeAmountUsd = 0;
            $exchangeAmount = 0;
            
            // Generate return code
            $returnCode = 'RT-' . date('Ymd') . '-' . str_pad(ReturnModel::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            
            // Create return record
            $return = ReturnModel::create([
                'return_code' => $returnCode,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'return_date' => $request->return_date,
                'type' => $type,
                'total_refund' => $totalRefundVnd,
                'total_refund_usd' => $totalRefundUsd,
                'exchange_amount' => $exchangeAmount,
                'exchange_amount_usd' => $exchangeAmountUsd,
                'exchange_rate' => $sale->exchange_rate ?? 0,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'status' => 'pending',
            ]);
            
            // Create return items
            foreach ($returnItems as $itemData) {
                $return->items()->create($itemData);
            }
            
            // Handle exchange items if type is exchange
            if ($type === 'exchange' && $request->has('exchange_items')) {
                $totalExchangeUsd = 0;
                $totalExchangeVnd = 0;
                
                // VALIDATION: Kiểm tra currency phải giống nhau
                $returnedCurrency = null;
                if (count($returnItems) > 0) {
                    $returnedCurrency = $returnItems[0]['currency'];
                }
                
                foreach ($request->exchange_items as $item) {
                    if (!isset($item['quantity']) || $item['quantity'] <= 0) continue;
                    
                    // Validate inventory and get currency
                    $currency = $item['currency'] ?? 'VND';
                    $exchangeRate = $sale->exchange_rate ?? 25000;
                    
                    // VALIDATION: Kiểm tra currency phải giống SP trả
                    if ($returnedCurrency && $returnedCurrency !== $currency) {
                        throw new \Exception("Không thể đổi chéo loại tiền! Sản phẩm cũ là {$returnedCurrency}, sản phẩm mới phải cùng loại tiền.");
                    }
                    
                    if ($item['item_type'] === 'painting') {
                        $painting = Painting::find($item['item_id']);
                        if (!$painting || $painting->quantity < $item['quantity']) {
                            throw new \Exception("Không đủ tồn kho cho sản phẩm");
                        }
                    } else {
                        $supply = Supply::find($item['item_id']);
                        if (!$supply || $supply->quantity < $item['quantity']) {
                            throw new \Exception("Không đủ tồn kho cho vật tư");
                        }
                    }
                    
                    // Tính giá dựa trên currency (KHÔNG quy đổi chéo)
                    if ($currency === 'USD') {
                        $unitPriceUsd = $item['unit_price_usd'] ?? 0;
                        $unitPriceVnd = 0; // Không quy đổi
                        $subtotalUsd = $item['quantity'] * $unitPriceUsd;
                        $subtotalVnd = 0;
                        $totalExchangeUsd += $subtotalUsd;
                    } else {
                        $unitPriceVnd = $item['unit_price'] ?? 0;
                        $unitPriceUsd = 0; // Không quy đổi
                        $subtotalUsd = 0;
                        $subtotalVnd = $item['quantity'] * $unitPriceVnd;
                        $totalExchangeVnd += $subtotalVnd;
                    }
                    
                    // Lưu exchange item với giá đúng currency (không quy đổi chéo)
                    $return->exchangeItems()->create([
                        'item_type' => $item['item_type'],
                        'item_id' => $item['item_id'],
                        'supply_id' => $item['supply_id'] ?? null,
                        'supply_length' => $item['supply_length'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPriceVnd,
                        'unit_price_usd' => $unitPriceUsd,
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'subtotal' => $subtotalVnd,
                        'subtotal_usd' => $subtotalUsd,
                        'currency' => $currency,
                    ]);
                }
                
                // Tính chênh lệch (RIÊNG USD VÀ VND)
                // Chênh lệch = SP mới - Số tiền đã trả cho SP cũ (không phải giá gốc SP cũ)
                $differenceUsd = $totalExchangeUsd - $totalRefundUsd;
                $differenceVnd = $totalExchangeVnd - $totalRefundVnd;
                
                // Xử lý từng loại tiền riêng biệt (không gộp chung)
                // USD
                if ($differenceUsd > 0) {
                    // Khách trả thêm
                    $exchangeAmountUsd = $differenceUsd;
                    $totalRefundUsd = 0;
                } else {
                    // Hoàn lại khách
                    $exchangeAmountUsd = 0;
                    $totalRefundUsd = abs($differenceUsd);
                }
                
                // VND
                if ($differenceVnd > 0) {
                    // Khách trả thêm
                    $exchangeAmount = $differenceVnd;
                    $totalRefundVnd = 0;
                } else {
                    // Hoàn lại khách
                    $exchangeAmount = 0;
                    $totalRefundVnd = abs($differenceVnd);
                }
                
                // Update return with final amounts
                $return->update([
                    'total_refund' => $totalRefundVnd,
                    'total_refund_usd' => $totalRefundUsd,
                    'exchange_amount' => $exchangeAmount,
                    'exchange_amount_usd' => $exchangeAmountUsd,
                ]);
            }
            
            // LƯU Ý: KHÔNG tạo payment ngay khi tạo phiếu đổi hàng
            // Payment sẽ được tạo khi phiếu được duyệt và hoàn thành
            // Lưu thông tin payment để xử lý sau
            if ($type === 'exchange') {
                // Parse payment amounts (xử lý cả USD và VND)
                $paymentUsd = 0;
                $paymentVnd = 0;
                
                if ($request->has('payment_usd') && $request->payment_usd) {
                    $paymentUsd = (float) str_replace(',', '', $request->payment_usd);
                }
                
                if ($request->has('payment_vnd') && $request->payment_vnd) {
                    $paymentVnd = (float) str_replace(',', '', $request->payment_vnd);
                }
                
                // Chỉ lưu nếu có thanh toán (USD hoặc VND)
                if ($paymentUsd > 0 || $paymentVnd > 0) {
                    $exchangeRate = $request->payment_exchange_rate ? 
                        (float) str_replace(',', '', $request->payment_exchange_rate) : 
                        ($sale->exchange_rate ?? 1);
                    
                    // Xác định loại nợ (USD hay VND)
                    $dueUsd = $return->exchange_amount_usd ?? 0;
                    $dueVnd = $return->exchange_amount ?? 0;
                    
                    // Quy đổi thanh toán chéo
                    $finalPaymentUsd = $paymentUsd;
                    $finalPaymentVnd = $paymentVnd;
                    
                    if ($dueUsd > 0 && $dueVnd == 0 && $paymentVnd > 0 && $exchangeRate > 0) {
                        // Nợ USD only, trả VND → Quy đổi VND sang USD
                        $convertedUsd = $paymentVnd / $exchangeRate;
                        $finalPaymentUsd += $convertedUsd;
                        $finalPaymentVnd = 0; // Đã quy đổi hết sang USD
                    } elseif ($dueVnd > 0 && $dueUsd == 0 && $paymentUsd > 0 && $exchangeRate > 0) {
                        // Nợ VND only, trả USD → Quy đổi USD sang VND
                        $convertedVnd = $paymentUsd * $exchangeRate;
                        $finalPaymentVnd += $convertedVnd;
                        $finalPaymentUsd = 0; // Đã quy đổi hết sang VND
                    }
                    
                    // Tính tổng payment amount (backward compatibility)
                    $paymentAmount = ($finalPaymentUsd * $exchangeRate) + $finalPaymentVnd;
                    
                    // Lưu thông tin payment vào notes của return để xử lý khi complete
                    $paymentInfo = [
                        'payment_amount' => $paymentAmount,
                        'payment_usd' => $finalPaymentUsd,
                        'payment_vnd' => $finalPaymentVnd,
                        'payment_exchange_rate' => $exchangeRate,
                        'payment_method' => $request->payment_method ?? 'cash',
                        'payment_date' => $request->return_date,
                        // Lưu thêm số tiền gốc để hiển thị
                        'original_payment_usd' => $paymentUsd,
                        'original_payment_vnd' => $paymentVnd,
                    ];
                    
                    $currentNotes = $return->notes ?? '';
                    $return->update([
                        'notes' => $currentNotes . "\n[PAYMENT_INFO]" . json_encode($paymentInfo)
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('returns.show', $return->id)
                ->with('success', 'Tạo phiếu đổi/trả thành công. Vui lòng chờ duyệt.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $return = ReturnModel::with([
            'sale.items',
            'customer',
            'processedBy',
            'items.painting',
            'items.supply',
            'items.frameSupply',
            'items.saleItem',
            'exchangeItems.painting',
            'exchangeItems.supply',
            'exchangeItems.frameSupply'
        ])->findOrFail($id);

        return view('returns.show', compact('return'));
    }

    public function edit($id)
    {
        $return = ReturnModel::with([
            'sale.items.painting',
            'sale.items.supply',
            'customer',
            'items'
        ])->findOrFail($id);

        if ($return->status !== 'pending') {
            return redirect()->route('returns.index')
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu đang chờ xử lý');
        }

        return view('returns.edit', compact('return'));
    }

    public function update(Request $request, $id)
    {
        // Convert formatted payment inputs to numbers
        $paymentUsd = $request->filled('payment_usd') ? (float)str_replace(',', '', $request->payment_usd) : 0;
        $paymentVnd = $request->filled('payment_vnd') ? (float)str_replace(',', '', $request->payment_vnd) : 0;
        $paymentExchangeRate = $request->filled('payment_exchange_rate') ? (float)str_replace(',', '', $request->payment_exchange_rate) : 25000;
        
        // Calculate total payment in VND for validation
        $totalPaymentVnd = ($paymentUsd * $paymentExchangeRate) + $paymentVnd;
        
        if ($totalPaymentVnd > 0) {
            $request->merge([
                'payment_amount' => $totalPaymentVnd,
                'payment_usd' => $paymentUsd,
                'payment_vnd' => $paymentVnd,
                'payment_exchange_rate' => $paymentExchangeRate,
            ]);
        }
        
        $request->validate([
            'return_date' => 'required|date',
            'type' => 'required|in:return,exchange',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|integer|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,card',
        ]);

        try {
            DB::beginTransaction();

            $return = ReturnModel::findOrFail($id);

            if ($return->status !== 'pending') {
                throw new \Exception('Chỉ có thể chỉnh sửa phiếu đang chờ xử lý');
            }

            // Get sale data for discount calculation
            $sale = $return->sale;

            // Validate that at least one item has quantity > 0
            $hasValidItems = false;
            foreach ($request->items as $itemData) {
                if (isset($itemData['quantity']) && $itemData['quantity'] > 0) {
                    $hasValidItems = true;
                    break;
                }
            }
            
            if (!$hasValidItems) {
                throw new \Exception('Phải có ít nhất một sản phẩm với số lượng trả > 0');
            }

            // Delete old items
            $return->items()->delete();
            $return->exchangeItems()->delete();

            // Calculate new total for return items (RIÊNG USD VÀ VND)
            $totalReturnValue = 0;
            $totalReturnValueUsd = 0;
            $totalReturnValueVnd = 0;
            
            foreach ($request->items as $itemData) {
                if (!isset($itemData['quantity']) || $itemData['quantity'] <= 0) {
                    continue;
                }
                
                $saleItem = SaleItem::findOrFail($itemData['sale_item_id']);
                
                // Determine item type and id
                if ($saleItem->painting_id) {
                    $itemType = 'painting';
                    $itemId = $saleItem->painting_id;
                } elseif ($saleItem->frame_id) {
                    $itemType = 'frame';
                    $itemId = $saleItem->frame_id;
                } else {
                    $itemType = 'supply';
                    $itemId = $saleItem->supply_id;
                }
                
                // Calculate unit price after applying discounts (RIÊNG USD VÀ VND)
                $currency = $saleItem->currency;
                $exchangeRate = $sale->exchange_rate ?? 25000;
                
                // Lấy giá gốc dựa trên currency - KHÔNG QUY ĐỔI
                if ($currency === 'USD') {
                    $unitPriceUsd = $saleItem->price_usd;
                    $unitPriceVnd = 0; // Không tính VND cho USD items
                } else {
                    $unitPriceVnd = $saleItem->price_vnd;
                    $unitPriceUsd = 0; // Không tính USD cho VND items
                }
                
                // Apply item-level discount if exists
                if ($saleItem->discount_percent > 0) {
                    if ($currency === 'USD') {
                        $unitPriceUsd = $unitPriceUsd * (1 - $saleItem->discount_percent / 100);
                    } else {
                        $unitPriceVnd = $unitPriceVnd * (1 - $saleItem->discount_percent / 100);
                    }
                }
                
                // Apply sale-level discount if exists
                if ($sale->discount_percent > 0) {
                    if ($currency === 'USD') {
                        $unitPriceUsd = $unitPriceUsd * (1 - $sale->discount_percent / 100);
                    } else {
                        $unitPriceVnd = $unitPriceVnd * (1 - $sale->discount_percent / 100);
                    }
                }
                
                $subtotalUsd = $itemData['quantity'] * $unitPriceUsd;
                $subtotalVnd = $itemData['quantity'] * $unitPriceVnd;
                $subtotal = $subtotalVnd; // Backward compatibility
                
                // Chỉ cộng vào tổng theo currency gốc
                if ($currency === 'USD') {
                    $totalReturnValueUsd += $subtotalUsd;
                } else {
                    $totalReturnValueVnd += $subtotalVnd;
                }
                $totalReturnValue += $subtotal;

                $return->items()->create([
                    'sale_item_id' => $saleItem->id,
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unitPriceVnd,
                    'unit_price_usd' => $unitPriceUsd,
                    'subtotal' => $subtotalVnd,
                    'subtotal_usd' => $subtotalUsd,
                    'reason' => $itemData['reason'] ?? null,
                ]);
            }

            // LOGIC MỚI: Tính theo tỷ lệ đã trả (RIÊNG USD VÀ VND)
            $type = $request->input('type', 'return');
            
            // Tính tỷ lệ đã trả của hóa đơn (riêng từng loại tiền)
            // QUAN TRỌNG: Dùng original_total (trước khi trả hàng) để tính tỷ lệ
            $originalTotalUsd = $sale->original_total_usd ?? $sale->total_usd;
            $originalTotalVnd = $sale->original_total_vnd ?? $sale->total_vnd;
            
            $paidRatioUsd = $originalTotalUsd > 0 ? ($sale->paid_usd / $originalTotalUsd) : 0;
            
            // Tính số tiền hoàn lại = MIN(Đã trả, Giá trị SP trả)
            // Logic: Chỉ hoàn lại số tiền thực tế đã trả, không vượt quá giá trị SP
            $totalRefundUsd = min($sale->paid_usd, $totalReturnValueUsd);
            $totalRefundVnd = min($sale->paid_vnd, $totalReturnValueVnd);
            
            $totalRefund = $totalRefundVnd; // Backward compatibility
            $exchangeAmount = null;
            $exchangeAmountUsd = null;
            
            if ($type === 'exchange' && $request->has('exchange_items')) {
                $totalExchange = 0;
                $totalExchangeUsd = 0;
                $totalExchangeVnd = 0;
                
                foreach ($request->exchange_items as $item) {
                    if (!isset($item['quantity']) || $item['quantity'] <= 0) continue;
                    
                    // Validate inventory and get currency
                    $currency = $item['currency'] ?? 'VND';
                    $exchangeRate = $sale->exchange_rate ?? 25000;
                    
                    if ($item['item_type'] === 'painting') {
                        $painting = Painting::find($item['item_id']);
                        if (!$painting || $painting->quantity < $item['quantity']) {
                            throw new \Exception("Không đủ tồn kho cho sản phẩm");
                        }
                    } else {
                        $supply = Supply::find($item['item_id']);
                        if (!$supply || $supply->quantity < $item['quantity']) {
                            throw new \Exception("Không đủ tồn kho cho vật tư");
                        }
                    }
                    
                    // Tính giá dựa trên currency
                    if ($currency === 'USD') {
                        $unitPriceUsd = $item['unit_price_usd'] ?? 0;
                        $unitPriceVnd = $unitPriceUsd * $exchangeRate;
                    } else {
                        $unitPriceVnd = $item['unit_price'] ?? 0;
                        $unitPriceUsd = $exchangeRate > 0 ? ($unitPriceVnd / $exchangeRate) : 0;
                    }
                    
                    // Tính subtotal (RIÊNG USD VÀ VND)
                    $subtotalUsd = $item['quantity'] * $unitPriceUsd;
                    $subtotalVnd = $item['quantity'] * $unitPriceVnd;
                    $subtotal = $subtotalVnd; // Backward compatibility
                    
                    $totalExchangeUsd += $subtotalUsd;
                    $totalExchangeVnd += $subtotalVnd;
                    $totalExchange += $subtotal;
                    
                    $return->exchangeItems()->create([
                        'item_type' => $item['item_type'],
                        'item_id' => $item['item_id'],
                        'supply_id' => $item['supply_id'] ?? null,
                        'supply_length' => $item['supply_length'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPriceVnd,
                        'unit_price_usd' => $unitPriceUsd,
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'subtotal' => $subtotal,
                        'subtotal_usd' => $subtotalUsd,
                        'currency' => $currency,
                    ]);
                }
                
                // Tính chênh lệch (RIÊNG USD VÀ VND)
                $differenceUsd = $totalExchangeUsd - $totalRefundUsd;
                $differenceVnd = $totalExchangeVnd - $totalRefundVnd;
                
                // Xử lý từng loại tiền riêng biệt (không gộp chung)
                // USD
                if ($differenceUsd > 0) {
                    $exchangeAmountUsd = $differenceUsd;
                    $totalRefundUsd = 0;
                } else {
                    $exchangeAmountUsd = 0;
                    $totalRefundUsd = abs($differenceUsd);
                }
                
                // VND
                if ($differenceVnd > 0) {
                    $exchangeAmount = $differenceVnd;
                    $totalRefundVnd = 0;
                } else {
                    $exchangeAmount = 0;
                    $totalRefundVnd = abs($differenceVnd);
                }
            } else {
                // Trả hàng thuần túy: Hoàn số tiền đã trả cho SP này (đã tính ở trên)
                // $totalRefundUsd và $totalRefundVnd đã được tính
            }

            // LƯU Ý: KHÔNG tạo payment ngay khi update phiếu đổi hàng
            // Payment sẽ được tạo khi phiếu được duyệt và hoàn thành
            // Lưu thông tin payment để xử lý sau
            // Update payment info in notes if exists
            $paymentUsd = $request->filled('payment_usd') ? (float)str_replace(',', '', $request->payment_usd) : 0;
            $paymentVnd = $request->filled('payment_vnd') ? (float)str_replace(',', '', $request->payment_vnd) : 0;
            
            if ($request->type === 'exchange' && ($paymentUsd > 0 || $paymentVnd > 0)) {
                $paymentExchangeRate = $request->filled('payment_exchange_rate') ? 
                    (float)str_replace(',', '', $request->payment_exchange_rate) : 
                    ($sale->exchange_rate ?? 1);
                
                // Xác định loại nợ (USD hay VND)
                $dueUsd = $exchangeAmountUsd ?? 0;
                $dueVnd = $exchangeAmount ?? 0;
                
                // Quy đổi thanh toán chéo
                $finalPaymentUsd = $paymentUsd;
                $finalPaymentVnd = $paymentVnd;
                
                if ($dueUsd > 0 && $dueVnd == 0 && $paymentVnd > 0 && $paymentExchangeRate > 0) {
                    // Nợ USD only, trả VND → Quy đổi VND sang USD
                    $convertedUsd = $paymentVnd / $paymentExchangeRate;
                    $finalPaymentUsd += $convertedUsd;
                    $finalPaymentVnd = 0;
                } elseif ($dueVnd > 0 && $dueUsd == 0 && $paymentUsd > 0 && $paymentExchangeRate > 0) {
                    // Nợ VND only, trả USD → Quy đổi USD sang VND
                    $convertedVnd = $paymentUsd * $paymentExchangeRate;
                    $finalPaymentVnd += $convertedVnd;
                    $finalPaymentUsd = 0;
                }
                
                $paymentAmount = ($finalPaymentUsd * $paymentExchangeRate) + $finalPaymentVnd;
                
                $paymentInfo = [
                    'payment_amount' => $paymentAmount,
                    'payment_usd' => $finalPaymentUsd,
                    'payment_vnd' => $finalPaymentVnd,
                    'payment_exchange_rate' => $paymentExchangeRate,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'payment_date' => $request->return_date,
                    'original_payment_usd' => $paymentUsd,
                    'original_payment_vnd' => $paymentVnd,
                ];
                
                // Remove old payment info if exists
                $notesToUpdate = $request->notes;
                if (strpos($notesToUpdate, '[PAYMENT_INFO]') !== false) {
                    $parts = explode('[PAYMENT_INFO]', $notesToUpdate);
                    $notesToUpdate = trim($parts[0]);
                }
                
                $notesToUpdate .= "\n[PAYMENT_INFO]" . json_encode($paymentInfo);
            } else {
                // If no payment or not exchange, just update notes (removing old payment info if any)
                $notesToUpdate = $request->notes;
                if (strpos($notesToUpdate, '[PAYMENT_INFO]') !== false) {
                    $parts = explode('[PAYMENT_INFO]', $notesToUpdate);
                    $notesToUpdate = trim($parts[0]);
                }
            }
            
            // Lưu tỷ giá của sale (đã tính $totalRefundUsd ở trên)
            $exchangeRate = $sale->exchange_rate ?? 0;
            // $totalRefundUsd và $exchangeAmountUsd đã được tính ở trên theo logic mới
            
            // Backward compatibility
            $totalRefund = $totalRefundVnd;
            $exchangeAmount = $exchangeAmount ?? 0;
            
            // Update return
            $return->update([
                'return_date' => $request->return_date,
                'type' => $type,
                'total_refund' => $totalRefund,
                'total_refund_usd' => $totalRefundUsd,
                'exchange_amount' => $exchangeAmount,
                'exchange_amount_usd' => $exchangeAmountUsd,
                'exchange_rate' => $exchangeRate,
                'reason' => $request->reason,
                'notes' => $notesToUpdate,
            ]);

            DB::commit();

            return redirect()->route('returns.show', $return->id)
                ->with('success', 'Cập nhật phiếu đổi/trả thành công');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function approve($id)
    {
        try {
            $return = ReturnModel::findOrFail($id);

            if ($return->status !== 'pending') {
                return back()->with('error', 'Chỉ có thể duyệt phiếu đang chờ duyệt');
            }

            // Check for potential conflicts before approving
            $conflicts = [];
            foreach ($return->items as $item) {
                $alreadyReturned = ReturnItem::where('sale_item_id', $item->sale_item_id)
                    ->whereHas('return', function($q) use ($return) {
                        $q->where('id', '!=', $return->id)
                          ->whereIn('status', ['approved', 'completed']);
                    })
                    ->sum('quantity');
                
                $availableQty = $item->saleItem->quantity - $alreadyReturned;
                
                if ($item->quantity > $availableQty) {
                    $itemName = $item->saleItem->painting_id ? 
                        ($item->saleItem->painting->name ?? 'N/A') : 
                        ($item->saleItem->supply->name ?? 'N/A');
                    
                    $conflicts[] = "Sản phẩm '{$itemName}': Yêu cầu trả {$item->quantity} nhưng chỉ còn {$availableQty} có thể trả";
                }
            }
            
            if (!empty($conflicts)) {
                $conflictMessage = "Không thể duyệt phiếu trả vì có xung đột:\n" . implode("\n", $conflicts);
                return back()->with('error', $conflictMessage);
            }

            // Check inventory availability for exchange items
            if ($return->type === 'exchange') {
                foreach ($return->exchangeItems as $item) {
                    if ($item->item_type === 'painting') {
                        $painting = Painting::find($item->item_id);
                        if (!$painting || $painting->quantity < $item->quantity) {
                            $itemName = $painting ? $painting->name : 'N/A';
                            $available = $painting ? $painting->quantity : 0;
                            $conflicts[] = "Sản phẩm đổi mới '{$itemName}': Tồn kho {$available}, Yêu cầu {$item->quantity}";
                        }
                    } else {
                        $supply = Supply::find($item->item_id);
                        if (!$supply || $supply->quantity < $item->quantity) {
                            $itemName = $supply ? $supply->name : 'N/A';
                            $available = $supply ? $supply->quantity : 0;
                            $conflicts[] = "Vật tư đổi mới '{$itemName}': Tồn kho {$available}, Yêu cầu {$item->quantity}";
                        }
                    }
                }
                
                if (!empty($conflicts)) {
                    $conflictMessage = "Không thể duyệt phiếu đổi hàng:\n" . implode("\n", $conflicts);
                    return back()->with('error', $conflictMessage);
                }
            }

            $return->update([
                'status' => 'approved',
                'processed_by' => Auth::id()
            ]);

            return back()->with('success', 'Đã duyệt phiếu đổi/trả');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function complete($id)
    {
        try {
            DB::beginTransaction();

            $return = ReturnModel::findOrFail($id);

            if ($return->status !== 'approved') {
                return back()->with('error', 'Chỉ có thể hoàn thành phiếu đã được duyệt');
            }

            // Check for duplicate returns (prevent double processing)
            foreach ($return->items as $item) {
                $alreadyReturned = ReturnItem::where('sale_item_id', $item->sale_item_id)
                    ->whereHas('return', function($q) use ($return) {
                        $q->where('id', '!=', $return->id)
                          ->whereIn('status', ['approved', 'completed']);
                    })
                    ->sum('quantity');
                
                $availableQty = $item->saleItem->quantity - $alreadyReturned;
                
                if ($item->quantity > $availableQty) {
                    $itemName = $item->saleItem->painting_id ? 
                        ($item->saleItem->painting->name ?? 'N/A') : 
                        ($item->saleItem->supply->name ?? 'N/A');
                    
                    throw new \Exception("Không thể hoàn thành phiếu trả. Sản phẩm '{$itemName}' đã được trả trong phiếu khác hoặc số lượng trả vượt quá số lượng có thể trả.");
                }
            }

            // Update inventory - Add returned items back to stock
            foreach ($return->items as $item) {
                // Return painting if exists
                if ($item->item_type === 'painting' && $item->item_id) {
                    $painting = Painting::find($item->item_id);
                    if ($painting) {
                        $painting->increment('quantity', $item->quantity);
                        
                        // Create inventory transaction
                        InventoryTransaction::create([
                            'transaction_type' => 'import',
                            'item_type' => 'painting',
                            'item_id' => $item->item_id,
                            'quantity' => $item->quantity,
                            'reference_type' => 'return',
                            'reference_id' => $return->id,
                            'transaction_date' => $return->return_date,
                            'notes' => ($return->type === 'exchange' ? 'Đổi hàng' : 'Trả hàng') . " - Phiếu {$return->return_code}",
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
                
                // Return supply if exists (can be with painting or standalone)
                if ($item->supply_id && $item->supply_length > 0) {
                    $supply = Supply::find($item->supply_id);
                    if ($supply) {
                        $totalLength = $item->supply_length * $item->quantity;
                        $supply->increment('quantity', $totalLength);
                        
                        // Create inventory transaction
                        InventoryTransaction::create([
                            'transaction_type' => 'import',
                            'item_type' => 'supply',
                            'item_id' => $item->supply_id,
                            'quantity' => $totalLength,
                            'reference_type' => 'return',
                            'reference_id' => $return->id,
                            'transaction_date' => $return->return_date,
                            'notes' => ($return->type === 'exchange' ? 'Đổi hàng' : 'Trả hàng') . " - Phiếu {$return->return_code}",
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            }

            // Update inventory - Subtract exchange items from stock
            if ($return->type === 'exchange') {
                foreach ($return->exchangeItems as $item) {
                    // Subtract main item (painting or supply)
                    if ($item->item_type === 'painting') {
                        $painting = Painting::find($item->item_id);
                        if ($painting) {
                            if ($painting->quantity < $item->quantity) {
                                throw new \Exception("Không đủ tồn kho cho sản phẩm: " . ($painting->name ?? 'N/A'));
                            }
                            $painting->decrement('quantity', $item->quantity);
                            
                            // Create inventory transaction
                            InventoryTransaction::create([
                                'transaction_type' => 'export',
                                'item_type' => 'painting',
                                'item_id' => $item->item_id,
                                'quantity' => $item->quantity,
                                'reference_type' => 'return',
                                'reference_id' => $return->id,
                                'transaction_date' => $return->return_date,
                                'notes' => "Đổi hàng (tranh) - Phiếu {$return->return_code}",
                                'created_by' => Auth::id(),
                            ]);
                        }
                    } else {
                        $supply = Supply::find($item->item_id);
                        if ($supply) {
                            $totalSupplyQty = $item->supply_length ? ($item->supply_length * $item->quantity) : $item->quantity;
                            if ($supply->quantity < $totalSupplyQty) {
                                throw new \Exception("Không đủ tồn kho cho vật tư: " . ($supply->name ?? 'N/A') . ". Cần: {$totalSupplyQty}, Tồn: {$supply->quantity}");
                            }
                            $supply->decrement('quantity', $totalSupplyQty);
                            
                            // Create inventory transaction
                            InventoryTransaction::create([
                                'transaction_type' => 'export',
                                'item_type' => 'supply',
                                'item_id' => $item->item_id,
                                'quantity' => $totalSupplyQty,
                                'reference_type' => 'return',
                                'reference_id' => $return->id,
                                'transaction_date' => $return->return_date,
                                'notes' => "Đổi hàng (vật tư) - Phiếu {$return->return_code}",
                                'created_by' => Auth::id(),
                            ]);
                        }
                    }
                    
                    // Subtract frame supply if exists (for paintings with frames)
                    if ($item->supply_id && $item->supply_length) {
                        $frameSupply = Supply::find($item->supply_id);
                        if ($frameSupply) {
                            $totalFrameLength = $item->supply_length * $item->quantity;
                            if ($frameSupply->quantity < $totalFrameLength) {
                                throw new \Exception("Không đủ tồn kho cho vật tư khung: " . ($frameSupply->name ?? 'N/A') . ". Cần: {$totalFrameLength}m, Tồn: {$frameSupply->quantity}m");
                            }
                            $frameSupply->decrement('quantity', $totalFrameLength);
                            
                            // Create inventory transaction for frame supply
                            InventoryTransaction::create([
                                'transaction_type' => 'export',
                                'item_type' => 'supply',
                                'item_id' => $item->supply_id,
                                'quantity' => $totalFrameLength,
                                'reference_type' => 'return',
                                'reference_id' => $return->id,
                                'transaction_date' => $return->return_date,
                                'notes' => "Đổi hàng (vật tư khung) - Phiếu {$return->return_code}",
                                'created_by' => Auth::id(),
                            ]);
                        }
                    }
                }
            }

            $sale = $return->sale;
            
            // LOGIC MỚI: Áp dụng cho TẤT CẢ phiếu
            if ($return->type === 'return') {
                // Đánh dấu các sản phẩm đã trả
                foreach ($return->items as $returnItem) {
                    $saleItem = $returnItem->saleItem;
                    $returnedQty = $returnItem->quantity;
                    
                    $saleItem->returned_quantity += $returnedQty;
                    
                    if ($saleItem->returned_quantity >= $saleItem->quantity) {
                        $saleItem->is_returned = true;
                    }
                    
                    $saleItem->save();
                }
                
                // Lưu original_total nếu chưa có (lần trả đầu tiên)
                if (!$sale->original_total_vnd) {
                    $sale->original_total_vnd = $sale->total_vnd;
                    $sale->original_total_usd = $sale->total_usd;
                }
                
                // Tính giá trị hàng trả (RIÊNG USD VÀ VND theo currency gốc của từng item)
                $returnedValueUsd = 0;
                $returnedValueVnd = 0;
                
                foreach ($return->items as $item) {
                    if ($item->currency === 'USD') {
                        $returnedValueUsd += $item->subtotal_usd;
                    } else {
                        $returnedValueVnd += $item->subtotal;
                    }
                }
                
                // Giảm total hiện tại (RIÊNG USD VÀ VND)
                $newTotalUsd = $sale->total_usd - $returnedValueUsd;
                $newTotalVnd = $sale->total_vnd - $returnedValueVnd;
                
                $sale->update([
                    'total_vnd' => $newTotalVnd,
                    'total_usd' => $newTotalUsd,
                    'original_total_vnd' => $sale->original_total_vnd,
                    'original_total_usd' => $sale->original_total_usd,
                ]);
                
                // Tính số tiền cần hoàn (RIÊNG USD VÀ VND)
                $paidUsd = $sale->paid_usd;
                $paidVnd = $sale->paid_vnd;
                $refundUsd = 0;
                $refundVnd = 0;
                
                // Chỉ hoàn nếu đã trả > total mới
                if ($paidUsd > $newTotalUsd) {
                    $refundUsd = $paidUsd - $newTotalUsd;
                }
                if ($paidVnd > $newTotalVnd) {
                    $refundVnd = $paidVnd - $newTotalVnd;
                }
                
                // Cập nhật total_refund vào return record
                $return->update([
                    'total_refund_usd' => $refundUsd,
                    'total_refund' => $refundVnd,
                ]);
                
                // Tạo payment hoàn tiền (nếu có)
                if ($refundUsd > 0 || $refundVnd > 0) {
                    Payment::create([
                        'sale_id' => $return->sale_id,
                        'payment_date' => now(),
                        'amount' => -($refundUsd * $sale->exchange_rate + $refundVnd), // Backward compatibility (VND total)
                        'payment_usd' => -$refundUsd,
                        'payment_vnd' => -$refundVnd,
                        'payment_exchange_rate' => $sale->exchange_rate,
                        'payment_method' => 'cash',
                        'transaction_type' => 'return',
                        'notes' => "Hoàn tiền cho phiếu trả {$return->return_code}",
                        'created_by' => Auth::id(),
                    ]);
                }
                
            } elseif ($return->type === 'exchange') {
                // Đổi hàng: Cập nhật sale_items
                
                // 1. Đánh dấu sản phẩm cũ là đã trả
                foreach ($return->items as $returnItem) {
                    $saleItem = $returnItem->saleItem;
                    $returnedQty = $returnItem->quantity;
                    
                    $saleItem->returned_quantity += $returnedQty;
                    
                    if ($saleItem->returned_quantity >= $saleItem->quantity) {
                        $saleItem->is_returned = true;
                    }
                    
                    $saleItem->save();
                }
                
                // 2. Thêm sản phẩm mới vào sale_items (với USD)
                foreach ($return->exchangeItems as $exchangeItem) {
                    $newSaleItem = SaleItem::create([
                        'sale_id' => $sale->id,
                        'painting_id' => $exchangeItem->item_type === 'painting' ? $exchangeItem->item_id : null,
                        'supply_id' => $exchangeItem->item_type === 'supply' ? $exchangeItem->item_id : null,
                        'description' => $exchangeItem->item_type === 'painting' 
                            ? ($exchangeItem->painting->name ?? 'N/A')
                            : ($exchangeItem->supply->name ?? 'N/A'),
                        'quantity' => $exchangeItem->quantity,
                        'supply_length' => $exchangeItem->supply_length ?? 0,
                        'currency' => $exchangeItem->currency ?? 'VND',
                        'price_usd' => $exchangeItem->unit_price_usd ?? 0,
                        'price_vnd' => $exchangeItem->unit_price,
                        'discount_percent' => $exchangeItem->discount_percent ?? 0,
                    ]);
                    
                    // Calculate totals for new item
                    $newSaleItem->calculateTotals();
                }
                
                // 3. Lưu original_total nếu chưa có
                if (!$sale->original_total_vnd) {
                    $sale->original_total_vnd = $sale->total_vnd;
                    $sale->original_total_usd = $sale->total_usd;
                }
                
                // 4. Tính lại total từ sale_items
                $sale->calculateTotals();
                
                // 5. Xử lý payment nếu có (khách trả thêm tiền)
                $this->processExchangePayment($return, $sale);
            }

            $return->update(['status' => 'completed']);

            // Update sale payment status
            $sale->fresh()->updatePaymentStatus();
            
            // Kiểm tra nếu trả hết hàng
            if ($return->type === 'return') {
                // Refresh sale để lấy dữ liệu mới nhất
                $sale->refresh();
                
                // Kiểm tra xem tất cả items đã được trả chưa (dựa trên is_returned flag)
                $totalItems = $sale->saleItems->count();
                $returnedItems = $sale->saleItems->where('is_returned', true)->count();
                
                // Hoặc kiểm tra total_usd và total_vnd = 0
                $allReturned = ($totalItems > 0 && $returnedItems >= $totalItems) || 
                               ($sale->total_usd == 0 && $sale->total_vnd == 0 && $totalItems > 0);

                if ($allReturned) {
                    // Trả hết hàng - set cancelled
                    $sale->update([
                        'sale_status' => 'cancelled',
                        'payment_status' => 'cancelled',
                        'debt_amount' => 0,
                        'debt_usd' => 0,
                        'debt_vnd' => 0,
                    ]);
                    
                    if ($sale->debt) {
                        $sale->debt->update([
                            'status' => 'cancelled',
                            'debt_amount' => 0,
                        ]);
                    }
                }
            }

            DB::commit();

            return back()->with('success', 'Đã hoàn thành phiếu đổi/trả');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function cancel($id)
    {
        try {
            DB::beginTransaction();

            $return = ReturnModel::findOrFail($id);

            if ($return->status === 'cancelled') {
                return back()->with('error', 'Phiếu đã bị hủy trước đó');
            }

            if ($return->status === 'completed') {
                return back()->with('error', 'Không thể hủy phiếu đã hoàn thành');
            }

            // Cancel the return
            $return->update(['status' => 'cancelled']);

            DB::commit();

            return back()->with('success', 'Đã hủy phiếu đổi/trả');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $return = ReturnModel::findOrFail($id);

            if ($return->status === 'completed') {
                return back()->with('error', 'Không thể xóa phiếu đã hoàn thành. Vui lòng hủy phiếu trước.');
            }

            $return->delete();

            return redirect()->route('returns.index')
                ->with('success', 'Đã xóa phiếu đổi/trả');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Xử lý payment cho đổi hàng khi hoàn thành phiếu
     */
    private function processExchangePayment($return, $sale)
    {
        // Tìm thông tin payment trong notes
        if (strpos($return->notes, '[PAYMENT_INFO]') !== false) {
            $parts = explode('[PAYMENT_INFO]', $return->notes);
            if (count($parts) > 1) {
                $paymentInfoJson = trim($parts[1]);
                $paymentInfo = json_decode($paymentInfoJson, true);
                
                if ($paymentInfo && isset($paymentInfo['payment_amount']) && $paymentInfo['payment_amount'] > 0) {
                    $paymentUsd = $paymentInfo['payment_usd'] ?? 0;
                    $paymentVnd = $paymentInfo['payment_vnd'] ?? 0;
                    $exchangeRate = $paymentInfo['payment_exchange_rate'] ?? 25000;
                    
                    // Lấy original values (số tiền gốc trước quy đổi)
                    $originalUsd = $paymentInfo['original_payment_usd'] ?? $paymentUsd;
                    $originalVnd = $paymentInfo['original_payment_vnd'] ?? $paymentVnd;
                    
                    // Tạo notes với thông tin original (để hiển thị đúng thanh toán chéo)
                    $paymentNotes = "Thanh toán đổi hàng - Phiếu {$return->return_code}";
                    if ($originalUsd != $paymentUsd || $originalVnd != $paymentVnd) {
                        $paymentNotes .= " [ORIGINAL:{$originalUsd},{$originalVnd}]";
                    }
                    
                    // Tạo payment record (với USD/VND riêng)
                    Payment::create([
                        'sale_id' => $sale->id,
                        'amount' => $paymentInfo['payment_amount'], // Backward compatibility
                        'payment_usd' => $paymentUsd,
                        'payment_vnd' => $paymentVnd,
                        'payment_exchange_rate' => $exchangeRate,
                        'payment_date' => $paymentInfo['payment_date'] ?? now(),
                        'payment_method' => $paymentInfo['payment_method'] ?? 'cash',
                        'transaction_type' => 'exchange_payment',
                        'notes' => $paymentNotes,
                        'created_by' => Auth::id(),
                    ]);
                    
                    // Dọn dẹp notes - xóa thông tin payment
                    $cleanNotes = trim($parts[0]);
                    $return->update(['notes' => $cleanNotes]);
                }
            }
        }
    }


}
