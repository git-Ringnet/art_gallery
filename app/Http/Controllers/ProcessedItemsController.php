<?php

namespace App\Http\Controllers;

use App\Models\ProcessedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryTransaction;

class ProcessedItemsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $query = ProcessedItem::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('processed_items.code', 'like', "%{$search}%")
                    ->orWhere('processed_items.name', 'like', "%{$search}%")
                    ->orWhereHas('saleItems.sale', function ($saleQuery) use ($search) {
                        $saleQuery->where('invoice_code', 'like', "%{$search}%");
                    });
            });
        }

        $items = $query->with(['saleItems.sale', 'inventoryTransactions'])
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Transform data to match inventory format
        $items->getCollection()->transform(function ($item) {
            $latestImport = $item->inventoryTransactions->where('transaction_type', 'import')->first();
            $latestExport = $item->inventoryTransactions->where('transaction_type', 'export')->first();
            $sales = $item->saleItems->map(fn($si) => $si->sale)->filter()->unique('id');

            return [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'type' => 'processed_item',
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'import_date' => $latestImport ? \Carbon\Carbon::parse($latestImport->transaction_date)->format('d/m/Y') : $item->created_at->format('d/m/Y'),
                'export_date' => $latestExport ? \Carbon\Carbon::parse($latestExport->transaction_date)->format('d/m/Y') : null,
                'sales' => $sales,
                'status' => $item->quantity > 0 ? 'in_stock' : 'out_of_stock',
                'price_vnd' => $item->price_vnd,
                'price_usd' => $item->price_usd,
                'notes' => $item->notes,
            ];
        });

        return view('processed_items.index', compact('items', 'search'));
    }

    /* Standalone Create/Edit disabled as per user request. 
       Processed items are created via "direct typing" in the Sales module. */
    /*
    public function create()
    {
        return view('processed_items.create');
    }

    public function store(Request $request)
    {
        // ...
    }
    */

    public function show(ProcessedItem $processedItem)
    {
        $processedItem->load(['saleItems.sale', 'inventoryTransactions.createdBy']);
        return view('processed_items.show', compact('processedItem'));
    }

    /*
    public function edit(ProcessedItem $processedItem)
    {
        // ...
    }

    public function update(Request $request, ProcessedItem $processedItem)
    {
        // ...
    }
    */

    public function destroy(ProcessedItem $processedItem)
    {
        // Check if there are any sales associated with this item
        if ($processedItem->saleItems()->exists()) {
            return back()->with('error', 'Không thể xóa hàng gia công đã có trong hóa đơn.');
        }

        $processedItem->delete();
        return redirect()->route('inventory.processed-items.index')->with('success', 'Đã xóa hàng gia công.');
    }

    public function bulkDelete(Request $request)
    {
        $items = json_decode($request->input('items'), true);

        if (empty($items)) {
            return redirect()->route('inventory.processed-items.index')
                ->with('error', 'Không có mục nào được chọn để xóa');
        }

        $deletedCount = 0;
        $errors = [];

        foreach ($items as $itemData) {
            $id = is_array($itemData) ? ($itemData['id'] ?? null) : $itemData;
            if (!$id) continue;

            try {
                $item = ProcessedItem::find($id);
                if ($item) {
                    if ($item->saleItems()->exists()) {
                        $errors[] = "Sản phẩm {$item->code} đã có trong hóa đơn";
                        continue;
                    }
                    $item->delete();
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Lỗi ID {$id}: " . $e->getMessage();
            }
        }

        $res = redirect()->route('inventory.processed-items.index');
        
        if ($deletedCount > 0) {
            $msg = "Đã xóa thành công {$deletedCount} sản phẩm.";
            if (!empty($errors)) {
                return $res->with('warning', $msg . " Một số mục không thể xóa: " . implode(', ', $errors));
            }
            return $res->with('success', $msg);
        }

        return $res->with('error', "Không thể xóa các sản phẩm đã chọn: " . implode(', ', $errors));
    }
}
