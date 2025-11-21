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
use App\Exports\PaintingTemplateExport;
use App\Exports\SupplyTemplateExport;
use App\Imports\PaintingImport;
use App\Imports\PaintingImportWithImages;
use App\Imports\SupplyImport;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $type = $request->get('type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Check view permission
        $canView = true;
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email !== 'admin@example.com') {
            $role = \Illuminate\Support\Facades\Auth::user()->role;
            if ($role) {
                $perm = $role->getModulePermissions('inventory');
                if (!$perm || !$perm->can_view) {
                    $canView = false;
                }
            }
        }

        if (!$canView) {
            $inventoryPaginator = new LengthAwarePaginator(
                [],
                0,
                10,
                1,
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

        // Get paintings with sale information
        $paintingsQuery = Painting::with(['saleItems.sale']);
        if ($search) {
            $paintingsQuery->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('artist', 'like', "%{$search}%")
                  ->orWhereHas('saleItems.sale', function($saleQuery) use ($search) {
                      $saleQuery->where('invoice_code', 'like', "%{$search}%")
                                ->where('sale_status', 'completed');
                  });
            });
        }
        // Check filter permissions
        $canFilterDate = true;
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email !== 'admin@example.com') {
            $role = \Illuminate\Support\Facades\Auth::user()->role;
            if ($role) {
                $perm = $role->getModulePermissions('inventory');
                if ($perm && !$perm->can_filter_by_date) {
                    $canFilterDate = false;
                }
            }
        }

        if ($canFilterDate) {
            if ($dateFrom) {
                $paintingsQuery->where('import_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $paintingsQuery->where('import_date', '<=', $dateTo);
            }
        }
        $paintings = $paintingsQuery->orderBy('created_at', 'desc')->get();

        // Get supplies (chỉ lấy những cây còn số lượng > 0)
        $suppliesQuery = Supply::where('tree_count', '>', 0);
        if ($search) {
            $suppliesQuery->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        if ($canFilterDate) {
            if ($dateFrom) {
                $suppliesQuery->where('import_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $suppliesQuery->where('import_date', '<=', $dateTo);
            }
        }
        $supplies = $suppliesQuery->orderBy('created_at', 'desc')->get();

        // Combine and filter by type
        $inventory = collect();
        if (!$type || $type === 'painting') {
            $inventory = $inventory->merge($paintings->map(function ($painting) {
                // Get completed sales for this painting
                $completedSales = $painting->saleItems()
                    ->whereHas('sale', function($q) {
                        $q->where('sale_status', 'completed');
                    })
                    ->with('sale')
                    ->get()
                    ->pluck('sale')
                    ->filter()
                    ->unique('id');
                
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
                    'sales' => $completedSales,
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
                    'supply_type' => $supply->type,
                    'quantity' => $supply->quantity,
                    'tree_count' => $supply->tree_count,
                    'unit' => $supply->unit,
                    'import_date' => $supply->import_date?->format('d/m/Y'),
                    'import_date_raw' => $supply->import_date,
                    'created_at' => $supply->created_at,
                    'status' => $supply->status,
                    'sales' => collect(),
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
            'price_usd' => 'nullable|numeric|min:0',
            'price_vnd' => 'nullable|numeric|min:0',
            'import_date' => 'required|date',
            'export_date' => 'nullable|date|after:import_date',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
        ], [
            'code.unique' => 'Mã tranh đã tồn tại trong hệ thống.',
            'code.required' => 'Vui lòng nhập mã tranh.',
        ]);

        // Validate that at least one price is provided
        if (empty($validated['price_usd']) && empty($validated['price_vnd'])) {
            return back()->withErrors(['price_usd' => 'Vui lòng nhập ít nhất một loại giá (USD hoặc VND).'])->withInput();
        }

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
            'price_usd' => $validated['price_usd'] ?? null,
            'price_vnd' => $validated['price_vnd'] ?? null,
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
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'unit' => 'required|string|max:20',
            'length_per_tree' => 'required|numeric|min:0',
            'tree_count' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
        ], [
            'code.required' => 'Vui lòng nhập mã vật tư.',
            'length_per_tree.required' => 'Vui lòng nhập chiều dài mỗi cây.',
            'tree_count.required' => 'Vui lòng nhập số lượng cây.',
        ]);

        // Kiểm tra xem đã có vật tư cùng mã, cùng tên và cùng chiều dài mỗi cây chưa
        $existingSupply = Supply::where('code', $validated['code'])
            ->where('name', $validated['name'])
            ->where('quantity', $validated['length_per_tree'])
            ->first();

        if ($existingSupply) {
            // Cập nhật số lượng cây
            $existingSupply->tree_count += $validated['tree_count'];
            $existingSupply->save();

            return redirect()->route('inventory.index')
                ->with('success', 'Đã cập nhật số lượng cây cho vật tư: ' . $existingSupply->name . ' (' . $existingSupply->code . ') - Thêm ' . $validated['tree_count'] . ' cây');
        }

        // Kiểm tra mã vật tư có trùng không
        if (Supply::where('code', $validated['code'])->exists()) {
            return back()
                ->withErrors(['code' => 'Mã vật tư đã tồn tại trong hệ thống.'])
                ->withInput();
        }

        // Xử lý upload hình ảnh
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('supplies', 'public');
        }

        // Tạo mới vật tư
        // Lưu chiều dài mỗi cây vào quantity (để dễ so sánh khi nhập lần sau)
        Supply::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'unit' => $validated['unit'],
            'quantity' => $validated['length_per_tree'], // Chiều dài mỗi cây
            'tree_count' => $validated['tree_count'], // Số lượng cây
            'notes' => $validated['notes'] ?? null,
            'image' => $imagePath,
        ]);

        $totalLength = $validated['length_per_tree'] * $validated['tree_count'];
        return redirect()->route('inventory.index')
            ->with('success', 'Đã nhập vật tư thành công: ' . $validated['tree_count'] . ' cây × ' . $validated['length_per_tree'] . 'cm = ' . $totalLength . 'cm tổng');
    }

    public function showPainting($id)
    {
        $painting = Painting::findOrFail($id);
        return view('inventory.paintings.show', compact('painting'));
    }

    public function editPainting(Request $request, $id)
    {
        $painting = Painting::findOrFail($id);
        
        // Không cho sửa tranh đã bán
        if ($painting->status !== 'in_stock') {
            return redirect()->route('inventory.index')
                ->with('error', 'Không thể chỉnh sửa tranh đã bán');
        }
        
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
            
            // Không cho cập nhật tranh đã bán
            if ($painting->status !== 'in_stock') {
                return redirect()->route('inventory.index')
                    ->with('error', 'Không thể cập nhật tranh đã bán');
            }

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
                'image' => 'nullable|image|max:5120',
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
        
        // Không cho xóa tranh đã bán
        if ($painting->status !== 'in_stock') {
            return redirect()->route('inventory.index')
                ->with('error', 'Không thể xóa tranh đã bán');
        }
        
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
            'tree_count' => 'nullable|integer|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
            'remove_image' => 'nullable|in:0,1',
        ], [
            'code.unique' => 'Mã vật tư đã tồn tại trong hệ thống.',
            'code.required' => 'Vui lòng nhập mã vật tư.',
        ]);

        // Remove old image if requested
        if ($request->input('remove_image') === '1' && $supply->image) {
            Storage::disk('public')->delete($supply->image);
            $validated['image'] = null;
        }

        // Replace with new image: delete old first
        if ($request->hasFile('image')) {
            if ($supply->image) {
                Storage::disk('public')->delete($supply->image);
            }
            $imagePath = $request->file('image')->store('supplies', 'public');
            $validated['image'] = $imagePath;
        }

        $supply->update($validated);

        return redirect()->route('inventory.index')
            ->with('success', 'Cập nhật vật tư thành công');
    }

    public function destroySupply($id)
    {
        $supply = Supply::findOrFail($id);
        
        // Không cho xóa vật tư đã sử dụng hết (quantity = 0)
        if ($supply->quantity <= 0) {
            return redirect()->route('inventory.index')
                ->with('error', 'Không thể xóa vật tư đã sử dụng hết');
        }
        
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

        // Check view permission
        $canView = true;
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email !== 'admin@example.com') {
            $role = \Illuminate\Support\Facades\Auth::user()->role;
            if ($role) {
                $perm = $role->getModulePermissions('inventory');
                if (!$perm || !$perm->can_view) {
                    $canView = false;
                }
            }
        }

        if (!$canView) {
            return Excel::download(new InventoryExport(collect([])), 'quan-ly-kho-' . date('Y-m-d-His') . '.xlsx');
        }

        // Get filtered data (same logic as index)
        $paintingsQuery = Painting::query();
        if ($search) {
            $paintingsQuery->search($search);
        }
        // Check filter permissions
        $canFilterDate = true;
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email !== 'admin@example.com') {
            $role = \Illuminate\Support\Facades\Auth::user()->role;
            if ($role) {
                $perm = $role->getModulePermissions('inventory');
                if ($perm && !$perm->can_filter_by_date) {
                    $canFilterDate = false;
                }
            }
        }

        if ($canFilterDate) {
            if ($dateFrom) {
                $paintingsQuery->where('import_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $paintingsQuery->where('import_date', '<=', $dateTo);
            }
        }
        $paintings = $paintingsQuery->orderBy('created_at', 'desc')->get();

        $suppliesQuery = Supply::query();
        if ($search) {
            $suppliesQuery->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }
        if ($canFilterDate) {
            if ($dateFrom) {
                $suppliesQuery->where('import_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $suppliesQuery->where('import_date', '<=', $dateTo);
            }
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
                $quantityDisplay = $supply->quantity . ' ' . $supply->unit;
                if ($supply->type == 'frame' && $supply->tree_count > 0) {
                    $totalLength = $supply->tree_count * $supply->quantity;
                    $quantityDisplay = $supply->tree_count . ' cây × ' . $supply->quantity . $supply->unit . '/cây = ' . number_format($totalLength, 2) . $supply->unit . ' tổng';
                }
                
                return [
                    'code' => $supply->code,
                    'name' => $supply->name,
                    'type' => 'Vật tư',
                    'quantity' => $quantityDisplay,
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

        // Check view permission
        $canView = true;
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email !== 'admin@example.com') {
            $role = \Illuminate\Support\Facades\Auth::user()->role;
            if ($role) {
                $perm = $role->getModulePermissions('inventory');
                if (!$perm || !$perm->can_view) {
                    $canView = false;
                }
            }
        }

        if (!$canView) {
            $pdf = Pdf::loadView('inventory.export-pdf', [
                'inventory' => collect([]),
                'search' => $search,
                'type' => $type,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'scope' => $scope
            ]);
            return $pdf->download('quan-ly-kho-' . date('Y-m-d-His') . '.pdf');
        }

        // Get filtered data (same logic as index)
        $paintingsQuery = Painting::query();
        if ($search) {
            $paintingsQuery->search($search);
        }
        // Check filter permissions
        $canFilterDate = true;
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email !== 'admin@example.com') {
            $role = \Illuminate\Support\Facades\Auth::user()->role;
            if ($role) {
                $perm = $role->getModulePermissions('inventory');
                if ($perm && !$perm->can_filter_by_date) {
                    $canFilterDate = false;
                }
            }
        }

        if ($canFilterDate) {
            if ($dateFrom) {
                $paintingsQuery->where('import_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $paintingsQuery->where('import_date', '<=', $dateTo);
            }
        }
        $paintings = $paintingsQuery->orderBy('created_at', 'desc')->get();

        $suppliesQuery = Supply::query();
        if ($search) {
            $suppliesQuery->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        }
        if ($canFilterDate) {
            if ($dateFrom) {
                $suppliesQuery->where('import_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $suppliesQuery->where('import_date', '<=', $dateTo);
            }
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
                $quantityDisplay = $supply->quantity . ' ' . $supply->unit;
                if ($supply->type == 'frame' && $supply->tree_count > 0) {
                    $totalLength = $supply->tree_count * $supply->quantity;
                    $quantityDisplay = $supply->tree_count . ' cây × ' . $supply->quantity . $supply->unit . '/cây = ' . number_format($totalLength, 2) . $supply->unit . ' tổng';
                }
                
                return [
                    'code' => $supply->code,
                    'name' => $supply->name,
                    'type' => 'Vật tư',
                    'quantity' => $quantityDisplay,
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

    // Download template files
    public function downloadPaintingTemplate()
    {
        return Excel::download(new PaintingTemplateExport(), 'mau-nhap-tranh.xlsx');
    }

    public function downloadSupplyTemplate()
    {
        return Excel::download(new SupplyTemplateExport(), 'mau-nhap-vat-tu.xlsx');
    }

    // Import from Excel
    public function importPaintingExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240',
        ], [
            'file.required' => 'Vui lòng chọn file Excel',
            'file.mimes' => 'File phải có định dạng .xlsx hoặc .xls',
            'file.max' => 'File không được vượt quá 10MB',
        ]);

        try {
            // Handle uploaded images
            $uploadedImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $originalName = $image->getClientOriginalName();
                    $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                    $extension = $image->getClientOriginalExtension();
                    
                    // Store image
                    $uniqueName = uniqid() . '_' . time() . '.' . $extension;
                    $filename = 'paintings/' . $uniqueName;
                    
                    // Save file using Storage facade
                    Storage::disk('public')->putFileAs('paintings', $image, $uniqueName);
                    
                    // Verify file was saved
                    $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'paintings' . DIRECTORY_SEPARATOR . $uniqueName);
                    if (!file_exists($fullPath)) {
                        Log::error('File not saved', ['path' => $fullPath]);
                    } else {
                        Log::info('File saved successfully', ['path' => $fullPath, 'size' => filesize($fullPath)]);
                    }
                    
                    // Map by code (filename without extension)
                    $uploadedImages[$nameWithoutExt] = $filename;
                    
                    Log::info('Uploaded image', [
                        'original' => $originalName,
                        'code' => $nameWithoutExt,
                        'stored' => $filename
                    ]);
                }
            }
            
        // Extract embedded images from Excel manually
        $excelImages = [];
        try {
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $drawings = $worksheet->getDrawingCollection();

            foreach ($drawings as $drawing) {
                $coordinates = $drawing->getCoordinates();
                if (strpos($coordinates, ':') !== false) {
                    $coordinates = explode(':', $coordinates)[0];
                }
                $row = (int) preg_replace('/[^0-9]/', '', $coordinates);
                
                if ($row <= 1) continue;

                $imageContent = null;
                $extension = 'jpg';

                if ($drawing instanceof Drawing) {
                    $imagePath = $drawing->getPath();
                    if (file_exists($imagePath)) {
                        $imageContent = file_get_contents($imagePath);
                        $extension = pathinfo($imagePath, PATHINFO_EXTENSION) ?: 'jpg';
                    }
                } elseif ($drawing instanceof MemoryDrawing) {
                    $gdImage = $drawing->getImageResource();
                    if ($gdImage) {
                        ob_start();
                        switch ($drawing->getMimeType()) {
                            case MemoryDrawing::MIMETYPE_PNG:
                                imagepng($gdImage);
                                $extension = 'png';
                                break;
                            case MemoryDrawing::MIMETYPE_GIF:
                                imagegif($gdImage);
                                $extension = 'gif';
                                break;
                            default:
                                imagejpeg($gdImage);
                                $extension = 'jpg';
                        }
                        $imageContent = ob_get_contents();
                        ob_end_clean();
                    }
                }

                if ($imageContent) {
                    $uniqueName = uniqid() . '_' . time() . '.' . $extension;
                    Storage::disk('public')->put('paintings/' . $uniqueName, $imageContent);
                    $excelImages[$row] = 'paintings/' . $uniqueName;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error extracting excel images: ' . $e->getMessage());
        }
        
        // Use new import class that handles images better
        $import = new PaintingImportWithImages($uploadedImages, $excelImages);
        Excel::import($import, $request->file('file'));

            $message = "Import thành công {$import->getImportedCount()} tranh";
            if ($import->getSkippedCount() > 0) {
                $message .= ", bỏ qua {$import->getSkippedCount()} dòng";
            }

            $errors = $import->getErrors();
            if (!empty($errors)) {
                return redirect()->route('inventory.index')
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }

            return redirect()->route('inventory.index')
                ->with('success', $message);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Dòng {$failure->row()}: " . implode(', ', $failure->errors());
            }
            Log::error('Import validation error', ['errors' => $errorMessages]);
            return redirect()->back()
                ->with('error', 'Lỗi validation dữ liệu')
                ->with('import_errors', $errorMessages)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Import painting excel error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi import: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function importSupplyExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240',
        ], [
            'file.required' => 'Vui lòng chọn file Excel',
            'file.mimes' => 'File phải có định dạng .xlsx hoặc .xls',
            'file.max' => 'File không được vượt quá 10MB',
        ]);

        try {
            // Handle uploaded images
            $uploadedImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $originalName = $image->getClientOriginalName();
                    $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                    $extension = $image->getClientOriginalExtension();
                    
                    // Store image
                    $uniqueName = uniqid() . '_' . time() . '.' . $extension;
                    $filename = 'supplies/' . $uniqueName;
                    
                    // Save file using Storage facade
                    Storage::disk('public')->putFileAs('supplies', $image, $uniqueName);
                    
                    // Verify file was saved
                    $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'supplies' . DIRECTORY_SEPARATOR . $uniqueName);
                    if (!file_exists($fullPath)) {
                        Log::error('Supply file not saved', ['path' => $fullPath]);
                    } else {
                        Log::info('Supply file saved successfully', ['path' => $fullPath, 'size' => filesize($fullPath)]);
                    }
                    
                    // Map by code (filename without extension)
                    $uploadedImages[$nameWithoutExt] = $filename;
                    
                    Log::info('Uploaded supply image', [
                        'original' => $originalName,
                        'code' => $nameWithoutExt,
                        'stored' => $filename
                    ]);
                }
            }

            $import = new SupplyImport($uploadedImages);
            Excel::import($import, $request->file('file'));

            $message = "Import thành công {$import->getImportedCount()} vật tư mới";
            if ($import->getUpdatedCount() > 0) {
                $message .= ", cập nhật {$import->getUpdatedCount()} vật tư";
            }
            if ($import->getSkippedCount() > 0) {
                $message .= ", bỏ qua {$import->getSkippedCount()} dòng";
            }

            $errors = $import->getErrors();
            if (!empty($errors)) {
                return redirect()->route('inventory.index')
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }

            return redirect()->route('inventory.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Import supply excel error', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi import: ' . $e->getMessage())
                ->withInput();
        }
    }
}
