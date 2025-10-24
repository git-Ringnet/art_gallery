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

        $returns = $query->orderBy('created_at', 'desc')->paginate(20)->appends($request->query());

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

        $sale = Sale::with(['items.painting', 'items.supply', 'customer'])
            ->where('invoice_code', $request->invoice_code)
            ->first();

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy hóa đơn'
            ], 404);
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
            
            // Add item name for display
            if ($item->painting_id) {
                $item->item_name = $item->painting->name ?? 'N/A';
            } else {
                $item->item_name = $item->supply->name ?? 'N/A';
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
                $itemType = $saleItem->painting_id ? 'painting' : 'supply';
                $itemId = $saleItem->painting_id ?: $saleItem->supply_id;
                
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
                    'supply_id' => $saleItem->supply_id,
                    'supply_length' => $saleItem->supply_length,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'reason' => $itemData['reason'] ?? null,
                ];
            }

            // Calculate actual refund amount based on what customer has paid
            // If customer paid less than total, refund proportionally
            $paidAmount = $sale->paid_amount;
            $totalAmount = $sale->total_vnd;
            
            // Calculate refund: only refund what customer actually paid
            if ($paidAmount >= $totalReturnValue) {
                // Customer paid enough, refund full value
                $totalRefund = $totalReturnValue;
            } else {
                // Customer paid less, refund only what they paid
                $totalRefund = $paidAmount;
            }

            // Calculate exchange amount if type is exchange
            $type = $request->input('type', 'return');
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
                $exchangeAmount = $totalExchange - $totalRefund;
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
        $request->validate([
            'return_date' => 'required|date',
            'type' => 'required|in:return,exchange',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity' => 'required|integer|min:0',
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
                $itemType = $saleItem->painting_id ? 'painting' : 'supply';
                $itemId = $saleItem->painting_id ?: $saleItem->supply_id;
                
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

            // Calculate actual refund (limited by paid amount)
            $paidAmount = $sale->paid_amount;
            $totalRefund = min($totalReturnValue, $paidAmount);

            // Handle exchange items if type is exchange
            $type = $request->input('type', 'return');
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
                $exchangeAmount = $totalExchange - $totalRefund;
            }

            // Update return
            $return->update([
                'return_date' => $request->return_date,
                'type' => $type,
                'total_refund' => $totalRefund,
                'exchange_amount' => $exchangeAmount,
                'reason' => $request->reason,
                'notes' => $request->notes,
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

            // Update sale totals based on return type
            $sale = $return->sale;
            
            if ($return->type === 'return') {
                // For returns: reduce sale total by returned value
                $returnedValue = $return->total_refund;
                $newTotalVnd = $sale->total_vnd - $returnedValue;
                $newTotalUsd = $sale->total_usd - ($returnedValue / $sale->exchange_rate);
                
                // Update sale totals
                $sale->update([
                    'total_vnd' => $newTotalVnd,
                    'total_usd' => $newTotalUsd,
                ]);
            } elseif ($return->type === 'exchange') {
                // For exchanges: update sale items to reflect the new products
                // Remove or reduce old items (set quantity to 0 instead of deleting to avoid foreign key issues)
                foreach ($return->items as $returnItem) {
                    $saleItem = $returnItem->saleItem;
                    // Reduce quantity or set to 0 if fully exchanged
                    if ($saleItem->quantity <= $returnItem->quantity) {
                        $saleItem->update([
                            'quantity' => 0,
                            'total_usd' => 0,
                            'total_vnd' => 0,
                        ]);
                    } else {
                        $newQty = $saleItem->quantity - $returnItem->quantity;
                        $saleItem->update([
                            'quantity' => $newQty,
                            'total_usd' => $newQty * $saleItem->price_usd * (1 - $saleItem->discount_percent / 100),
                            'total_vnd' => $newQty * $saleItem->price_vnd * (1 - $saleItem->discount_percent / 100),
                        ]);
                    }
                }
                
                // Add new exchange items to sale
                foreach ($return->exchangeItems as $exchangeItem) {
                    // Calculate price before discount
                    $discountPercent = $exchangeItem->discount_percent ?? 0;
                    $priceBeforeDiscount = $discountPercent > 0 ? 
                        $exchangeItem->unit_price / (1 - $discountPercent / 100) : 
                        $exchangeItem->unit_price;
                    
                    $priceUsd = $priceBeforeDiscount / $sale->exchange_rate;
                    $priceVnd = $priceBeforeDiscount;
                    
                    // Get item description
                    $description = '';
                    if ($exchangeItem->item_type === 'painting') {
                        $painting = Painting::find($exchangeItem->item_id);
                        $description = $painting ? $painting->name : 'N/A';
                    } else {
                        $supply = Supply::find($exchangeItem->item_id);
                        $description = $supply ? $supply->name : 'N/A';
                    }
                    
                    // Determine supply_id and supply_length
                    // If item is painting with frame supply, use exchangeItem's supply info
                    // If item is supply itself, use item_id as supply_id
                    $saleSupplyId = null;
                    $saleSupplyLength = null;
                    
                    if ($exchangeItem->item_type === 'painting' && $exchangeItem->supply_id) {
                        // Painting with frame supply
                        $saleSupplyId = $exchangeItem->supply_id;
                        $saleSupplyLength = $exchangeItem->supply_length > 0 ? $exchangeItem->supply_length : null;
                    } elseif ($exchangeItem->item_type === 'supply') {
                        // Supply item
                        $saleSupplyId = $exchangeItem->item_id;
                        $saleSupplyLength = $exchangeItem->supply_length > 0 ? $exchangeItem->supply_length : null;
                    }
                    
                    $saleItemData = [
                        'painting_id' => $exchangeItem->item_type === 'painting' ? $exchangeItem->item_id : null,
                        'description' => $description,
                        'quantity' => $exchangeItem->quantity,
                        'currency' => 'VND',
                        'price_usd' => $priceUsd,
                        'price_vnd' => $priceVnd,
                        'discount_percent' => $discountPercent,
                        'total_usd' => $exchangeItem->subtotal / $sale->exchange_rate,
                        'total_vnd' => $exchangeItem->subtotal,
                    ];
                    
                    // Only add supply_id and supply_length if they exist
                    if ($saleSupplyId) {
                        $saleItemData['supply_id'] = $saleSupplyId;
                        $saleItemData['supply_length'] = $saleSupplyLength;
                    }
                    
                    $sale->items()->create($saleItemData);
                }
                
                // Recalculate sale totals
                $sale->calculateTotals();
            }
            
            // Create payment based on type
            if ($return->type === 'return') {
                // Full refund for return
                Payment::create([
                    'sale_id' => $return->sale_id,
                    'payment_date' => $return->return_date,
                    'amount' => -$return->total_refund,
                    'payment_method' => 'cash',
                    'notes' => "Hoàn tiền cho phiếu trả {$return->return_code}",
                    'created_by' => Auth::id(),
                ]);
            } elseif ($return->type === 'exchange' && $return->exchange_amount != 0) {
                // Payment for exchange difference
                Payment::create([
                    'sale_id' => $return->sale_id,
                    'payment_date' => $return->return_date,
                    'amount' => $return->exchange_amount,
                    'payment_method' => 'cash',
                    'notes' => "Chênh lệch đổi hàng {$return->return_code}" . ($return->exchange_amount > 0 ? ' (Thu thêm)' : ' (Hoàn lại)'),
                    'created_by' => Auth::id(),
                ]);
            }

            $return->update(['status' => 'completed']);

            // Update sale payment status based on return type
            if ($return->type === 'return') {
                $sale = $return->sale->fresh(); // Refresh to get updated totals
                $totalSaleItems = $sale->items->sum('quantity');
                $totalReturnedItems = ReturnItem::whereHas('return', function($q) use ($sale) {
                    $q->where('sale_id', $sale->id)
                      ->where('status', 'completed')
                      ->where('type', 'return'); // Only count returns, not exchanges
                })->sum('quantity');

                if ($totalReturnedItems >= $totalSaleItems) {
                    // All items returned, cancel the sale
                    $sale->update(['payment_status' => 'cancelled']);
                    
                    // Update debt if exists
                    if ($sale->debt) {
                        $sale->debt->update(['status' => 'cancelled']);
                    }
                } else {
                    // Partial return, update payment status
                    $sale->updatePaymentStatus();
                }
            } elseif ($return->type === 'exchange') {
                // For exchanges: just update payment status, don't change sale status
                // Sale remains active with updated items
                $sale->fresh()->updatePaymentStatus();
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
     * Recalculate sale totals for all completed returns
     * This method fixes the issue where completed returns didn't reduce sale totals
     */
    public function recalculateSaleTotals()
    {
        try {
            DB::beginTransaction();

            // Get all sales that have completed returns
            $salesWithReturns = Sale::whereHas('returns', function($q) {
                $q->where('status', 'completed');
            })->get();

            foreach ($salesWithReturns as $sale) {
                // Calculate total returned value for this sale (only returns, not exchanges)
                $totalReturnedValue = ReturnItem::whereHas('return', function($q) use ($sale) {
                    $q->where('sale_id', $sale->id)
                      ->where('status', 'completed')
                      ->where('type', 'return'); // Only count returns, not exchanges
                })->sum('subtotal');

                if ($totalReturnedValue > 0) {
                    // Get original sale total (before any returns)
                    $originalTotalVnd = $sale->subtotal_vnd - $sale->discount_vnd;
                    
                    // Calculate new total after returns
                    $newTotalVnd = $originalTotalVnd - $totalReturnedValue;
                    $newTotalUsd = $newTotalVnd / $sale->exchange_rate;

                    // Update sale totals
                    $sale->update([
                        'total_vnd' => $newTotalVnd,
                        'total_usd' => $newTotalUsd,
                    ]);

                    // Update payment status
                    $sale->updatePaymentStatus();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật lại tổng hóa đơn cho ' . $salesWithReturns->count() . ' hóa đơn có phiếu trả'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
