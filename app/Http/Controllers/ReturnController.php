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

        // Search by return code, invoice code, or customer
        if ($request->filled('search')) {
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

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('return_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('return_date', '<=', $request->to_date);
        }

        $returns = $query->orderBy('created_at', 'desc')->paginate(10)->appends($request->query());

        return view('returns.index', compact('returns'));
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
            
            // Calculate unit price after applying discounts
            $unitPrice = $item->price_vnd;
            
            // Apply item-level discount if exists
            if ($item->discount_percent > 0) {
                $unitPrice = $unitPrice * (1 - $item->discount_percent / 100);
            }
            
            // Apply sale-level discount if exists
            if ($sale->discount_percent > 0) {
                $unitPrice = $unitPrice * (1 - $sale->discount_percent / 100);
            }
            
            $item->unit_price = $unitPrice;
        }

        return response()->json([
            'success' => true,
            'sale' => $sale,
            'returned_quantities' => $returnedQuantities
        ]);
    }

    public function store(Request $request)
    {
        // Convert VND formatted payment_amount to number
        if ($request->filled('payment_amount')) {
            $request->merge([
                'payment_amount' => (float) str_replace([',', '.'], '', $request->payment_amount)
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
            
            // Calculate total value of returned items
            $totalReturnValue = 0;
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
                
                // Calculate unit price after applying discounts
                $unitPrice = $saleItem->price_vnd;
                
                // Apply item-level discount if exists
                if ($saleItem->discount_percent > 0) {
                    $unitPrice = $unitPrice * (1 - $saleItem->discount_percent / 100);
                }
                
                // Apply sale-level discount if exists
                if ($sale->discount_percent > 0) {
                    $unitPrice = $unitPrice * (1 - $sale->discount_percent / 100);
                }
                
                $subtotal = $itemData['quantity'] * $unitPrice;
                $totalReturnValue += $subtotal;

                $returnItems[] = [
                    'sale_item_id' => $saleItem->id,
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'quantity' => $itemData['quantity'],
                    'supply_id' => $saleItem->supply_id ?? null,
                    'supply_length' => $saleItem->supply_length ?? 0,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'reason' => $itemData['reason'] ?? null,
                ];
            }

            // LOGIC ĐÚNG: Tính theo tỷ lệ đã trả
            $paidAmount = $sale->paid_amount;
            $currentTotal = $sale->total_vnd;
            $type = $request->input('type', 'return');
            
            // Tính tỷ lệ đã trả của hóa đơn
            $paidRatio = $currentTotal > 0 ? ($paidAmount / $currentTotal) : 0;
            
            // Tính số tiền đã trả cho các sản phẩm đang trả
            $paidForReturnedItems = $totalReturnValue * $paidRatio;
            
            $totalRefund = 0;
            $exchangeAmount = null;
            $exchangeItemsData = [];
            
            if ($type === 'exchange' && $request->has('exchange_items')) {
                $totalExchange = 0;
                foreach ($request->exchange_items as $item) {
                    if (!isset($item['quantity']) || $item['quantity'] <= 0) continue;
                    
                    // Validate inventory availability
                    if ($item['item_type'] === 'painting') {
                        $painting = Painting::find($item['item_id']);
                        if (!$painting) {
                            throw new \Exception("Không tìm thấy sản phẩm");
                        }
                        if ($painting->quantity < $item['quantity']) {
                            throw new \Exception("Không đủ tồn kho cho sản phẩm '{$painting->name}'. Tồn kho: {$painting->quantity}, Yêu cầu: {$item['quantity']}");
                        }
                    } else {
                        $supply = Supply::find($item['item_id']);
                        if (!$supply) {
                            throw new \Exception("Không tìm thấy vật tư");
                        }
                        if ($supply->quantity < $item['quantity']) {
                            throw new \Exception("Không đủ tồn kho cho vật tư '{$supply->name}'. Tồn kho: {$supply->quantity}, Yêu cầu: {$item['quantity']}");
                        }
                    }
                    
                    $subtotal = $item['quantity'] * $item['unit_price'];
                    $totalExchange += $subtotal;
                    
                    $exchangeItemsData[] = [
                        'item_type' => $item['item_type'],
                        'item_id' => $item['item_id'],
                        'supply_id' => $item['supply_id'] ?? null,
                        'supply_length' => $item['supply_length'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'subtotal' => $subtotal,
                    ];
                }
                
                // Tính chênh lệch giữa giá SP mới và số tiền đã trả cho SP cũ
                $difference = $totalExchange - $paidForReturnedItems;
                
                if ($difference > 0) {
                    // Khách trả thêm
                    $exchangeAmount = $difference;
                    $totalRefund = 0;
                } else {
                    // Hoàn lại khách
                    $exchangeAmount = 0;
                    $totalRefund = abs($difference);
                }
            } else {
                // Trả hàng thuần túy: Hoàn số tiền đã trả cho SP này
                $totalRefund = $paidForReturnedItems;
            }

            // Create return record
            $return = ReturnModel::create([
                'return_code' => ReturnModel::generateReturnCode(),
                'type' => $type,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'return_date' => $request->return_date,
                'total_refund' => $totalRefund,
                'exchange_amount' => $exchangeAmount,
                'reason' => $request->reason,
                'status' => 'pending',
                'processed_by' => Auth::id(),
                'notes' => $request->notes,
            ]);

            // Create return items (không update inventory ngay, chờ duyệt)
            foreach ($returnItems as $itemData) {
                $return->items()->create($itemData);
            }

            // Create exchange items if any
            foreach ($exchangeItemsData as $itemData) {
                $return->exchangeItems()->create($itemData);
            }
            
            // LƯU Ý: KHÔNG tạo payment ngay khi tạo phiếu đổi hàng
            // Payment sẽ được tạo khi phiếu được duyệt và hoàn thành
            // Lưu thông tin payment để xử lý sau
            if ($type === 'exchange' && $request->filled('payment_amount')) {
                $paymentAmount = $request->payment_amount;
                if ($paymentAmount > 0) {
                    // Lưu thông tin payment vào notes của return để xử lý khi complete
                    $paymentInfo = [
                        'payment_amount' => $paymentAmount,
                        'payment_method' => $request->payment_method ?? 'cash',
                        'payment_date' => $request->return_date
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
            'sale',
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
        // Convert VND formatted payment_amount to number
        if ($request->filled('payment_amount')) {
            $request->merge([
                'payment_amount' => (float) str_replace([',', '.'], '', $request->payment_amount)
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

            // Calculate new total for return items
            $totalReturnValue = 0;
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
                
                // Calculate unit price after applying discounts
                $unitPrice = $saleItem->price_vnd;
                
                // Apply item-level discount if exists
                if ($saleItem->discount_percent > 0) {
                    $unitPrice = $unitPrice * (1 - $saleItem->discount_percent / 100);
                }
                
                // Apply sale-level discount if exists
                if ($sale->discount_percent > 0) {
                    $unitPrice = $unitPrice * (1 - $sale->discount_percent / 100);
                }
                
                $subtotal = $itemData['quantity'] * $unitPrice;
                $totalReturnValue += $subtotal;

                $return->items()->create([
                    'sale_item_id' => $saleItem->id,
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'reason' => $itemData['reason'] ?? null,
                ]);
            }

            // LOGIC ĐÚNG: Tính theo tỷ lệ đã trả
            $paidAmount = $sale->paid_amount;
            $currentTotal = $sale->total_vnd;
            $type = $request->input('type', 'return');
            
            // Tính tỷ lệ đã trả của hóa đơn
            $paidRatio = $currentTotal > 0 ? ($paidAmount / $currentTotal) : 0;
            
            // Tính số tiền đã trả cho các sản phẩm đang trả
            $paidForReturnedItems = $totalReturnValue * $paidRatio;
            
            $totalRefund = 0;
            $exchangeAmount = null;
            
            if ($type === 'exchange' && $request->has('exchange_items')) {
                $totalExchange = 0;
                foreach ($request->exchange_items as $item) {
                    if (!isset($item['quantity']) || $item['quantity'] <= 0) continue;
                    
                    // Validate inventory
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
                    
                    $subtotal = $item['quantity'] * $item['unit_price'];
                    $totalExchange += $subtotal;
                    
                    $return->exchangeItems()->create([
                        'item_type' => $item['item_type'],
                        'item_id' => $item['item_id'],
                        'supply_id' => $item['supply_id'] ?? null,
                        'supply_length' => $item['supply_length'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_percent' => $item['discount_percent'] ?? 0,
                        'subtotal' => $subtotal,
                    ]);
                }
                
                // Tính chênh lệch giữa giá SP mới và số tiền đã trả cho SP cũ
                $difference = $totalExchange - $paidForReturnedItems;
                
                if ($difference > 0) {
                    // Khách trả thêm
                    $exchangeAmount = $difference;
                    $totalRefund = 0;
                } else {
                    // Hoàn lại khách
                    $exchangeAmount = 0;
                    $totalRefund = abs($difference);
                }
            } else {
                // Trả hàng thuần túy: Hoàn số tiền đã trả cho SP này
                $totalRefund = $paidForReturnedItems;
            }

            // LƯU Ý: KHÔNG tạo payment ngay khi update phiếu đổi hàng
            // Payment sẽ được tạo khi phiếu được duyệt và hoàn thành
            // Lưu thông tin payment để xử lý sau
            $notesToUpdate = $request->notes;
            if ($type === 'exchange' && $request->filled('payment_amount')) {
                $paymentAmount = $request->payment_amount;
                if ($paymentAmount > 0) {
                    // Lưu thông tin payment vào notes của return để xử lý khi complete
                    $paymentInfo = [
                        'payment_amount' => $paymentAmount,
                        'payment_method' => $request->payment_method ?? 'cash',
                        'payment_date' => $request->return_date
                    ];
                    
                    $notesToUpdate = ($notesToUpdate ?? '') . "\n[PAYMENT_INFO]" . json_encode($paymentInfo);
                }
            }
            
            // Update return
            $return->update([
                'return_date' => $request->return_date,
                'type' => $type,
                'total_refund' => $totalRefund,
                'exchange_amount' => $exchangeAmount,
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
                
                // Tính giá trị hàng trả lần này
                $returnedValue = $return->items->sum('subtotal');
                
                // Giảm total hiện tại
                $newTotalVnd = $sale->total_vnd - $returnedValue;
                $newTotalUsd = $newTotalVnd / $sale->exchange_rate;
                
                $sale->update([
                    'total_vnd' => $newTotalVnd,
                    'total_usd' => $newTotalUsd,
                    'original_total_vnd' => $sale->original_total_vnd,
                    'original_total_usd' => $sale->original_total_usd,
                ]);
                
                // Tính số tiền cần hoàn
                $paidAmount = $sale->payments()->sum('amount');
                $refundAmount = 0;
                
                // Chỉ hoàn nếu đã trả > total mới
                if ($paidAmount > $newTotalVnd) {
                    $refundAmount = $paidAmount - $newTotalVnd;
                }
                
                if ($refundAmount > 0) {
                    Payment::create([
                        'sale_id' => $return->sale_id,
                        'payment_date' => now(),
                        'amount' => -$refundAmount,
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
                
                // 2. Thêm sản phẩm mới vào sale_items
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
                        'currency' => 'VND',
                        'price_usd' => 0,
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
                $totalSaleItems = $sale->items->sum('quantity');
                $totalReturnedItems = ReturnItem::whereHas('return', function($q) use ($sale) {
                    $q->where('sale_id', $sale->id)
                      ->where('status', 'completed')
                      ->where('type', 'return');
                })->sum('quantity');

                if ($totalReturnedItems >= $totalSaleItems) {
                    // Trả hết hàng - set cancelled
                    $sale->update([
                        'sale_status' => 'cancelled',
                        'payment_status' => 'cancelled',
                        'debt_amount' => 0,
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
                    // Tạo payment record
                    Payment::create([
                        'sale_id' => $sale->id,
                        'amount' => $paymentInfo['payment_amount'],
                        'payment_date' => $paymentInfo['payment_date'] ?? now(),
                        'payment_method' => $paymentInfo['payment_method'] ?? 'cash',
                        'transaction_type' => 'exchange_payment',
                        'notes' => "Thanh toán đổi hàng - Phiếu {$return->return_code}",
                        'created_by' => Auth::id(),
                    ]);
                    
                    // Update sale paid_amount
                    $sale->increment('paid_amount', $paymentInfo['payment_amount']);
                    
                    // Dọn dẹp notes - xóa thông tin payment
                    $cleanNotes = trim($parts[0]);
                    $return->update(['notes' => $cleanNotes]);
                }
            }
        }
    }


}
