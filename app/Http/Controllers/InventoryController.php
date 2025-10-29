<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Painting;
use App\Models\Supply;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InventoryExport;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $type = $request->get('type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Get paintings
        $paintingsQuery = Painting::query();
        if ($search) {
            $paintingsQuery->search($search);
        }
        if ($dateFrom) {
            $paintingsQuery->where('import_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $paintingsQuery->where('import_date', '<=', $dateTo);
        }
        $paintings = $paintingsQuery->orderBy('created_at', 'desc')->get();

        // Get supplies
        $suppliesQuery = Supply::query();
        if ($search) {
            $suppliesQuery->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }
        if ($dateFrom) {
            $suppliesQuery->where('import_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $suppliesQuery->where('import_date', '<=', $dateTo);
        }
        $supplies = $suppliesQuery->orderBy('created_at', 'desc')->get();

        // Combine and filter by type
        $inventory = collect();
        if (!$type || $type === 'painting') {
            $inventory = $inventory->merge($paintings->map(function ($painting) {
                return [
                    'id' => $painting->id,
                    'code' => $painting->code,
                    'name' => $painting->name,
                    'type' => 'painting',
                    'quantity' => $painting->quantity,
                    'import_date' => $painting->import_date?->format('d/m/Y'),
                    'import_date_raw' => $painting->import_date,
                    'created_at' => $painting->created_at,
                    'status' => $painting->status,
                    'artist' => $painting->artist,
                    'material' => $painting->material,
                    'price_usd' => $painting->price_usd,
                ];
            }));
        }
        if (!$type || $type === 'supply') {
            $inventory = $inventory->merge($supplies->map(function ($supply) {
                return [
                    'id' => $supply->id,
                    'code' => $supply->code,
                    'name' => $supply->name,
                    'type' => 'supply',
                    'quantity' => $supply->quantity,
                    'unit' => $supply->unit,
                    'import_date' => $supply->import_date?->format('d/m/Y'),
                    'import_date_raw' => $supply->import_date,
                    'created_at' => $supply->created_at,
                    'status' => $supply->status,
                ];
            }));
        }

        // Sort by created_at (newest first) - this ensures consistent ordering
        $inventory = $inventory->sortByDesc(function ($item) {
            return $item['created_at'] ? $item['created_at']->timestamp : 0;
        })->values();

        // Paginate merged collection
        $perPage = 10;
        $currentPage = (int) ($request->get('page', 1));
        $total = $inventory->count();
        $itemsForCurrentPage = $inventory->forPage($currentPage, $perPage)->values();

        $inventoryPaginator = new LengthAwarePaginator(
            $itemsForCurrentPage,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => route('inventory.index'),
                'query' => $request->query(),
            ]
        );

        return view('inventory.index', [
            'inventory' => $inventoryPaginator,
            'search' => $search,
            'type' => $type,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function import()
    {
        return view('inventory.import');
    }

    public function importPaintingForm()
    {
        return view('inventory.import-painting');
    }

    public function importSupplyForm()
    {
        return view('inventory.import-supply');
    }

    public function importPainting(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:paintings,code',
            'name' => 'required|string|max:255',
            'artist' => 'required|string|max:255',
            'material' => 'required|string|max:100',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'year' => 'nullable',
            'price' => 'required|numeric|min:0',
            'import_date' => 'required|date',
            'export_date' => 'nullable|date|after:import_date',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ], [
            'code.unique' => 'Mã tranh đã tồn tại trong hệ thống.',
            'code.required' => 'Vui lòng nhập mã tranh.',
        ]);

        // Persist painting
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('paintings', 'public');
        }

        Painting::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'artist' => $validated['artist'],
            'material' => $validated['material'],
            'width' => $validated['width'] ?? null,
            'height' => $validated['height'] ?? null,
            'paint_year' => $validated['year'] ?? null,
            'price_usd' => $validated['price'],
            'price_vnd' => null,
            'image' => $imagePath,
            'quantity' => 1,
            'import_date' => $validated['import_date'],
            'export_date' => $validated['export_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'in_stock',
        ]);

        return redirect()->route('inventory.index')
            ->with('success', 'Đã nhập tranh thành công');
    }

    public function importSupply(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:supplies,code',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'unit' => 'required|string|max:20',
            'quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ], [
            'code.unique' => 'Mã vật tư đã tồn tại trong hệ thống.',
            'code.required' => 'Vui lòng nhập mã vật tư.',
        ]);

        Supply::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'unit' => $validated['unit'],
            'quantity' => $validated['quantity'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('inventory.index')
            ->with('success', 'Đã nhập vật tư thành công');
    }

    public function showPainting($id)
    {
        $painting = Painting::findOrFail($id);
        return view('inventory.paintings.show', compact('painting'));
    }

    public function editPainting(Request $request, $id)
    {
        $painting = Painting::findOrFail($id);
        
        // Store the return URL in session
        if ($request->has('return_url')) {
            session(['painting_edit_return_url' => $request->get('return_url')]);
        }
        
        return view('inventory.paintings.edit', compact('painting'));
    }

    public function updatePainting(Request $request, $id)
    {
        try {
            $painting = Painting::findOrFail($id);

            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:paintings,code,' . $id,
                'name' => 'required|string|max:255',
                'artist' => 'required|string|max:255',
                'material' => 'required|string|max:100',
                'width' => 'nullable|numeric',
                'height' => 'nullable|numeric',
                'paint_year' => 'nullable',
                'price_usd' => 'required|numeric|min:0',
                'import_date' => 'nullable|date',
                'export_date' => 'nullable|date|after:import_date',
                'notes' => 'nullable|string',
                'image' => 'nullable|image|max:2048',
                'remove_image' => 'nullable|in:0,1',
            ], [
                'code.unique' => 'Mã tranh đã tồn tại trong hệ thống.',
                'code.required' => 'Vui lòng nhập mã tranh.',
            ]);

            // Remove old image if requested
            if ($request->input('remove_image') === '1' && $painting->image) {
                Storage::disk('public')->delete($painting->image);
                $validated['image'] = null;
            }

            // Replace with new image: delete old first
            if ($request->hasFile('image')) {
                if ($painting->image) {
                    Storage::disk('public')->delete($painting->image);
                }
                $imagePath = $request->file('image')->store('paintings', 'public');
                $validated['image'] = $imagePath;
            }

            $updateResult = $painting->update($validated);
            
            Log::info('Painting update result', [
                'painting_id' => $id,
                'update_result' => $updateResult,
                'validated_data' => $validated
            ]);

            // Get the return URL from session or default to index
            $returnUrl = session('painting_edit_return_url', route('inventory.index'));
            session()->forget('painting_edit_return_url');

            return redirect($returnUrl)
                ->with('success', 'Cập nhật tranh thành công');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in updatePainting', [
                'errors' => $e->errors(),
                'painting_id' => $id
            ]);
            return back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            Log::error('Error updating painting', [
                'painting_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroyPainting($id)
    {
        $painting = Painting::findOrFail($id);
        if ($painting->image) {
            Storage::disk('public')->delete($painting->image);
        }
        $painting->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Đã xóa tranh');
    }

    public function showSupply($id)
    {
        $supply = Supply::findOrFail($id);
        return view('inventory.supplies.show', compact('supply'));
    }

    // Supplies
    public function editSupply($id)
    {
        $supply = Supply::findOrFail($id);
        $types = Supply::getTypes();
        return view('inventory.supplies.edit', compact('supply', 'types'));
    }

    public function updateSupply(Request $request, $id)
    {
        $supply = Supply::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:supplies,code,' . $id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:frame,canvas,other',
            'unit' => 'required|string|max:20',
            'quantity' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ], [
            'code.unique' => 'Mã vật tư đã tồn tại trong hệ thống.',
            'code.required' => 'Vui lòng nhập mã vật tư.',
        ]);

        $supply->update($validated);

        return redirect()->route('inventory.index')
            ->with('success', 'Cập nhật vật tư thành công');
    }

    public function destroySupply($id)
    {
        $supply = Supply::findOrFail($id);
        $supply->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Đã xóa vật tư');
    }

    public function exportExcel(Request $request)
    {
        $search = $request->get('search');
        $type = $request->get('type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $scope = $request->get('scope', 'all'); // 'current' or 'all'
        $currentPage = (int) $request->get('page', 1);
        $perPage = 10;

        // Get filtered data (same logic as index)
        $paintingsQuery = Painting::query();
        if ($search) {
            $paintingsQuery->search($search);
        }
        if ($dateFrom) {
            $paintingsQuery->where('import_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $paintingsQuery->where('import_date', '<=', $dateTo);
        }
        $paintings = $paintingsQuery->orderBy('created_at', 'desc')->get();

        $suppliesQuery = Supply::query();
        if ($search) {
            $suppliesQuery->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }
        if ($dateFrom) {
            $suppliesQuery->where('import_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $suppliesQuery->where('import_date', '<=', $dateTo);
        }
        $supplies = $suppliesQuery->orderBy('created_at', 'desc')->get();

        $inventory = collect();
        if (!$type || $type === 'painting') {
            $inventory = $inventory->merge($paintings->map(function ($painting) {
                return [
                    'code' => $painting->code,
                    'name' => $painting->name,
                    'type' => 'Tranh',
                    'quantity' => $painting->quantity,
                    'unit' => '',
                    'import_date' => $painting->import_date?->format('d/m/Y'),
                    'status' => $painting->status == 'in_stock' ? 'Còn hàng' : 'Đã bán',
                    'artist' => $painting->artist,
                    'material' => $painting->material,
                    'price_usd' => $painting->price_usd,
                    'created_at' => $painting->created_at,
                ];
            }));
        }
        if (!$type || $type === 'supply') {
            $inventory = $inventory->merge($supplies->map(function ($supply) {
                return [
                    'code' => $supply->code,
                    'name' => $supply->name,
                    'type' => 'Vật tư',
                    'quantity' => $supply->quantity,
                    'unit' => $supply->unit,
                    'import_date' => $supply->import_date?->format('d/m/Y'),
                    'status' => $supply->quantity > 0 ? 'Còn hàng' : 'Hết hàng',
                    'artist' => '',
                    'material' => '',
                    'price_usd' => '',
                    'created_at' => $supply->created_at,
                ];
            }));
        }

        // Sort by created_at
        $inventory = $inventory->sortByDesc(function ($item) {
            return $item['created_at'] ? $item['created_at']->timestamp : 0;
        })->values();

        // If scope is 'current', only export current page
        if ($scope === 'current') {
            $inventory = $inventory->forPage($currentPage, $perPage)->values();
        }

        $filename = 'quan-ly-kho-' . date('Y-m-d-His') . '.xlsx';

        return Excel::download(new InventoryExport($inventory), $filename);
    }

    public function exportPdf(Request $request)
    {
        $search = $request->get('search');
        $type = $request->get('type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $scope = $request->get('scope', 'all');
        $currentPage = (int) $request->get('page', 1);
        $perPage = 10;

        // Get filtered data (same logic as index)
        $paintingsQuery = Painting::query();
        if ($search) {
            $paintingsQuery->search($search);
        }
        if ($dateFrom) {
            $paintingsQuery->where('import_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $paintingsQuery->where('import_date', '<=', $dateTo);
        }
        $paintings = $paintingsQuery->orderBy('created_at', 'desc')->get();

        $suppliesQuery = Supply::query();
        if ($search) {
            $suppliesQuery->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }
        if ($dateFrom) {
            $suppliesQuery->where('import_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $suppliesQuery->where('import_date', '<=', $dateTo);
        }
        $supplies = $suppliesQuery->orderBy('created_at', 'desc')->get();

        $inventory = collect();
        if (!$type || $type === 'painting') {
            $inventory = $inventory->merge($paintings->map(function ($painting) {
                return [
                    'code' => $painting->code,
                    'name' => $painting->name,
                    'type' => 'Tranh',
                    'quantity' => $painting->quantity,
                    'unit' => '',
                    'import_date' => $painting->import_date?->format('d/m/Y'),
                    'status' => $painting->status == 'in_stock' ? 'Còn hàng' : 'Đã bán',
                    'created_at' => $painting->created_at,
                ];
            }));
        }
        if (!$type || $type === 'supply') {
            $inventory = $inventory->merge($supplies->map(function ($supply) {
                return [
                    'code' => $supply->code,
                    'name' => $supply->name,
                    'type' => 'Vật tư',
                    'quantity' => $supply->quantity,
                    'unit' => $supply->unit,
                    'import_date' => $supply->import_date?->format('d/m/Y'),
                    'status' => $supply->quantity > 0 ? 'Còn hàng' : 'Hết hàng',
                    'created_at' => $supply->created_at,
                ];
            }));
        }

        // Sort by created_at
        $inventory = $inventory->sortByDesc(function ($item) {
            return $item['created_at'] ? $item['created_at']->timestamp : 0;
        })->values();

        // If scope is 'current', only export current page
        if ($scope === 'current') {
            $inventory = $inventory->forPage($currentPage, $perPage)->values();
        }

        $pdf = Pdf::loadView('inventory.export-pdf', compact('inventory', 'search', 'type', 'dateFrom', 'dateTo', 'scope'));

        $filename = 'quan-ly-kho-' . date('Y-m-d-His') . '.pdf';
        return $pdf->download($filename);
    }
}
