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

        // Get returned quantities for each item
        $returnedQuantities = [];
        foreach ($sale->items as $item) {
            $returned = ReturnItem::where('sale_item_id', $item->id)
                ->whereHas('return', function($q) {
                    $q->where('status', '!=', 'cancelled');
                })
                ->sum('quantity');
            $returnedQuantities[$item->id] = $returned;
            
            // Add item name for display
            if ($item->painting_id) {
                $item->item_name = $item->painting->name ?? 'N/A';
            } else {
                $item->item_name = $item->supply->name ?? 'N/A';
            }
            
            // Use price_vnd as unit_price
            $item->unit_price = $item->price_vnd;
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
            'items.*.quantity' => 'required|integer|min:1',
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
            
            // Calculate total value of returned items
            $totalReturnValue = 0;
            $returnItems = [];

            foreach ($request->items as $itemData) {
                if (!isset($itemData['quantity']) || $itemData['quantity'] <= 0) {
                    continue; // Skip items with 0 quantity
                }
                
                $saleItem = SaleItem::findOrFail($itemData['sale_item_id']);
                
                // Check if quantity is valid
                $alreadyReturned = ReturnItem::where('sale_item_id', $saleItem->id)
                    ->whereHas('return', function($q) {
                        $q->where('status', '!=', 'cancelled');
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
                $unitPrice = $saleItem->price_vnd;
                
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
                    
                    $subtotal = $item['quantity'] * $item['unit_price'];
                    $totalExchange += $subtotal;
                    
                    $exchangeItemsData[] = [
                        'item_type' => $item['item_type'],
                        'item_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
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
            'exchangeItems.painting',
            'exchangeItems.supply'
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
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $return = ReturnModel::findOrFail($id);

            if ($return->status !== 'pending') {
                throw new \Exception('Chỉ có thể chỉnh sửa phiếu đang chờ xử lý');
            }

            // Delete old items
            $return->items()->delete();

            // Calculate new total
            $totalRefund = 0;
            foreach ($request->items as $itemData) {
                if (!isset($itemData['quantity']) || $itemData['quantity'] <= 0) {
                    continue;
                }
                
                $saleItem = SaleItem::findOrFail($itemData['sale_item_id']);
                $itemType = $saleItem->painting_id ? 'painting' : 'supply';
                $itemId = $saleItem->painting_id ?: $saleItem->supply_id;
                $unitPrice = $saleItem->price_vnd;
                $subtotal = $itemData['quantity'] * $unitPrice;
                $totalRefund += $subtotal;

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

            // Update return
            $return->update([
                'return_date' => $request->return_date,
                'type' => $request->type,
                'total_refund' => $totalRefund,
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

            // Update inventory - Add returned items back to stock
            foreach ($return->items as $item) {
                // Return painting if exists
                if ($item->item_type === 'painting' && $item->item_id) {
                    $painting = Painting::find($item->item_id);
                    if ($painting) {
                        $painting->increment('quantity', $item->quantity);
                    }
                }
                
                // Return supply if exists (can be with painting or standalone)
                if ($item->supply_id && $item->supply_length > 0) {
                    $supply = Supply::find($item->supply_id);
                    if ($supply) {
                        $totalLength = $item->supply_length * $item->quantity;
                        $supply->increment('quantity', $totalLength);
                    }
                }
            }

            // Update inventory - Subtract exchange items from stock
            if ($return->type === 'exchange') {
                foreach ($return->exchangeItems as $item) {
                    if ($item->item_type === 'painting') {
                        $painting = Painting::find($item->item_id);
                        if ($painting) {
                            if ($painting->quantity < $item->quantity) {
                                throw new \Exception("Không đủ tồn kho cho sản phẩm: " . ($painting->name ?? 'N/A'));
                            }
                            $painting->decrement('quantity', $item->quantity);
                        }
                    } else {
                        $supply = Supply::find($item->item_id);
                        if ($supply) {
                            if ($supply->quantity < $item->quantity) {
                                throw new \Exception("Không đủ tồn kho cho sản phẩm: " . ($supply->name ?? 'N/A'));
                            }
                            $supply->decrement('quantity', $item->quantity);
                        }
                    }
                }
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

            // Check if all items are returned, if yes, cancel the sale
            $sale = $return->sale;
            $totalSaleItems = $sale->items->sum('quantity');
            $totalReturnedItems = ReturnItem::whereHas('return', function($q) use ($sale) {
                $q->where('sale_id', $sale->id)
                  ->where('status', 'completed');
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
}
