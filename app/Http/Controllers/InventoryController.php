<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Painting;
use App\Models\Supply;
use App\Services\ActivityLogger;
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
    protected $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function index(Request $request)
    {
        $search = $request->get('search');
        $type = $request->get('type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $perPage = 10;
        $currentPage = (int) ($request->get('page', 1));

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
                $perPage,
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

        // ==========================================
        // OPTIMIZED: Use database-level pagination
        // ==========================================

        // Case 1: Only paintings
        if ($type === 'painting') {
            return $this->indexPaintingsOnly($request, $search, $dateFrom, $dateTo, $canFilterDate, $perPage, $currentPage);
        }

        // Case 2: Only supplies
        if ($type === 'supply') {
            return $this->indexSuppliesOnly($request, $search, $dateFrom, $dateTo, $canFilterDate, $perPage, $currentPage);
        }

        // Case 3: Both types - use optimized lightweight pagination
        return $this->indexBothTypes($request, $search, $dateFrom, $dateTo, $canFilterDate, $perPage, $currentPage);
    }

    /**
     * OPTIMIZED: Index only paintings with database-level pagination
     */
    private function indexPaintingsOnly($request, $search, $dateFrom, $dateTo, $canFilterDate, $perPage, $currentPage)
    {
        $paintingsQuery = Painting::query();

        if ($search) {
            $paintingsQuery->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('artist', 'like', "%{$search}%")
                    ->orWhereHas('saleItems.sale', function ($saleQuery) use ($search) {
                        $saleQuery->where('invoice_code', 'like', "%{$search}%")
                            ->where('sale_status', 'completed');
                    });
            });
        }

        if ($canFilterDate) {
            if ($dateFrom) {
                $paintingsQuery->where('import_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $paintingsQuery->where('import_date', '<=', $dateTo);
            }
        }

        // Use database pagination
        $paginatedPaintings = $paintingsQuery->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // Get IDs for current page to load sales efficiently
        $paintingIds = $paginatedPaintings->pluck('id')->toArray();

        // Load completed sales for paintings on current page only (batch query)
        $completedSalesMap = $this->getCompletedSalesForPaintings($paintingIds);

        // Transform to expected format
        $items = $paginatedPaintings->getCollection()->map(function ($painting) use ($completedSalesMap) {
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
                'sales' => $completedSalesMap[$painting->id] ?? collect(),
            ];
        });

        $inventoryPaginator = new LengthAwarePaginator(
            $items,
            $paginatedPaintings->total(),
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
            'type' => 'painting',
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * OPTIMIZED: Index only supplies with database-level pagination
     */
    private function indexSuppliesOnly($request, $search, $dateFrom, $dateTo, $canFilterDate, $perPage, $currentPage)
    {
        $suppliesQuery = Supply::where('tree_count', '>', 0);

        if ($search) {
            $suppliesQuery->where(function ($q) use ($search) {
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

        // Use database pagination
        $paginatedSupplies = $suppliesQuery->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // Transform to expected format
        $items = $paginatedSupplies->getCollection()->map(function ($supply) {
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
        });

        $inventoryPaginator = new LengthAwarePaginator(
            $items,
            $paginatedSupplies->total(),
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
            'type' => 'supply',
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * OPTIMIZED: Index both paintings and supplies
     * Uses lightweight ID-based pagination first, then loads full data only for current page
     */
    private function indexBothTypes($request, $search, $dateFrom, $dateTo, $canFilterDate, $perPage, $currentPage)
    {
        // Step 1: Get lightweight data (only id, created_at) for pagination calculation
        $paintingsQuery = Painting::select('id', 'created_at');
        if ($search) {
            $paintingsQuery->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('artist', 'like', "%{$search}%")
                    ->orWhereHas('saleItems.sale', function ($saleQuery) use ($search) {
                        $saleQuery->where('invoice_code', 'like', "%{$search}%")
                            ->where('sale_status', 'completed');
                    });
            });
        }
        if ($canFilterDate) {
            if ($dateFrom) {
                $paintingsQuery->where('import_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $paintingsQuery->where('import_date', '<=', $dateTo);
            }
        }

        $suppliesQuery = Supply::select('id', 'created_at')->where('tree_count', '>', 0);
        if ($search) {
            $suppliesQuery->where(function ($q) use ($search) {
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

        // Get lightweight data
        $paintingIds = $paintingsQuery->orderBy('created_at', 'desc')->get();
        $supplyIds = $suppliesQuery->orderBy('created_at', 'desc')->get();

        // Step 2: Merge with type marker (lightweight objects)
        $allItems = collect();
        foreach ($paintingIds as $p) {
            $allItems->push(['id' => $p->id, 'type' => 'painting', 'created_at' => $p->created_at]);
        }
        foreach ($supplyIds as $s) {
            $allItems->push(['id' => $s->id, 'type' => 'supply', 'created_at' => $s->created_at]);
        }

        // Step 3: Sort and paginate IDs only
        $allItems = $allItems->sortBy([
            ['created_at', 'desc'],
            ['id', 'desc'],
        ])->values();

        $total = $allItems->count();
        $itemsForPage = $allItems->forPage($currentPage, $perPage)->values();

        // Step 4: Get IDs for current page only
        $paintingIdsOnPage = $itemsForPage->where('type', 'painting')->pluck('id')->toArray();
        $supplyIdsOnPage = $itemsForPage->where('type', 'supply')->pluck('id')->toArray();

        // Step 5: Load full data ONLY for items on current page (max 10 items)
        $paintings = collect();
        if (!empty($paintingIdsOnPage)) {
            $paintings = Painting::whereIn('id', $paintingIdsOnPage)->get()->keyBy('id');
        }

        $supplies = collect();
        if (!empty($supplyIdsOnPage)) {
            $supplies = Supply::whereIn('id', $supplyIdsOnPage)->get()->keyBy('id');
        }

        // Load completed sales for paintings on current page (batch query)
        $completedSalesMap = $this->getCompletedSalesForPaintings($paintingIdsOnPage);

        // Step 6: Build final collection in correct sort order
        $inventory = $itemsForPage->map(function ($item) use ($paintings, $supplies, $completedSalesMap) {
            if ($item['type'] === 'painting' && isset($paintings[$item['id']])) {
                $painting = $paintings[$item['id']];
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
                    'sales' => $completedSalesMap[$painting->id] ?? collect(),
                ];
            } elseif ($item['type'] === 'supply' && isset($supplies[$item['id']])) {
                $supply = $supplies[$item['id']];
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
            }
            return null;
        })->filter()->values();

        $inventoryPaginator = new LengthAwarePaginator(
            $inventory,
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
            'type' => null,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Get completed sales for multiple paintings in a single batch query
     * Eliminates N+1 query problem
     */
    private function getCompletedSalesForPaintings(array $paintingIds): array
    {
        if (empty($paintingIds)) {
            return [];
        }

        $saleItems = \App\Models\SaleItem::whereIn('painting_id', $paintingIds)
            ->whereHas('sale', function ($q) {
                $q->where('sale_status', 'completed');
            })
            ->with([
                'sale' => function ($q) {
                    $q->where('sale_status', 'completed');
                }
            ])
            ->get();

        $salesMap = [];
        foreach ($saleItems as $saleItem) {
            if ($saleItem->sale) {
                $paintingId = $saleItem->painting_id;
                if (!isset($salesMap[$paintingId])) {
                    $salesMap[$paintingId] = collect();
                }
                // Add unique sales only
                if (!$salesMap[$paintingId]->contains('id', $saleItem->sale->id)) {
                    $salesMap[$paintingId]->push($saleItem->sale);
                }
            }
        }

        return $salesMap;
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
            'width' => 'nullable|numeric|min:0|max:100000',
            'height' => 'nullable|numeric|min:0|max:100000',
            'year' => 'nullable',
            'price_usd' => 'required|numeric|min:0',
            'price_vnd' => 'nullable|numeric|min:0',
            'import_date' => 'required|date',
            'export_date' => 'nullable|date|after:import_date',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
        ], [
            'code.unique' => 'Mã tranh đã tồn tại trong hệ thống.',
            'code.required' => 'Vui lòng nhập mã tranh.',
            'name.required' => 'Vui lòng nhập tên tranh.',
            'artist.required' => 'Vui lòng nhập tên họa sĩ.',
            'material.required' => 'Vui lòng chọn chất liệu tranh.',
            'price_usd.required' => 'Vui lòng nhập giá USD.',
            'price_usd.numeric' => 'Giá USD phải là số.',
            'price_usd.min' => 'Giá USD phải lớn hơn hoặc bằng 0.',
            'import_date.required' => 'Vui lòng chọn ngày nhập kho.',
            'width.max' => 'Chiều rộng không được vượt quá 100,000 cm.',
            'height.max' => 'Chiều cao không được vượt quá 100,000 cm.',
            'image.max' => 'Kích thước ảnh không được vượt quá 5MB.',
        ]);

        // Persist painting
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('paintings', 'public');
        }

        $painting = Painting::create([
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

        // Log activity
        $this->activityLogger->logCreate(
            \App\Models\ActivityLog::MODULE_INVENTORY,
            $painting,
            "Nhập tranh mới: {$painting->code} - {$painting->name}"
        );

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
            $oldTreeCount = $existingSupply->tree_count;
            $existingSupply->tree_count += $validated['tree_count'];
            $existingSupply->save();

            // Log activity
            $this->activityLogger->logUpdate(
                \App\Models\ActivityLog::MODULE_INVENTORY,
                $existingSupply,
                ['tree_count' => ['old' => $oldTreeCount, 'new' => $existingSupply->tree_count]],
                "Cập nhật số lượng cây vật tư: {$existingSupply->code} - {$existingSupply->name} (Thêm {$validated['tree_count']} cây)"
            );

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
        $supply = Supply::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'unit' => $validated['unit'],
            'quantity' => $validated['length_per_tree'], // Chiều dài mỗi cây
            'tree_count' => $validated['tree_count'], // Số lượng cây
            'notes' => $validated['notes'] ?? null,
            'image' => $imagePath,
        ]);

        // Log activity
        $totalLength = $validated['length_per_tree'] * $validated['tree_count'];
        $this->activityLogger->logCreate(
            \App\Models\ActivityLog::MODULE_INVENTORY,
            $supply,
            "Nhập vật tư mới: {$supply->code} - {$supply->name} ({$validated['tree_count']} cây × {$validated['length_per_tree']}cm)"
        );

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
                'width' => 'nullable|numeric|min:0|max:100000',
                'height' => 'nullable|numeric|min:0|max:100000',
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
                'width.max' => 'Chiều rộng không được vượt quá 100,000 cm.',
                'height.max' => 'Chiều cao không được vượt quá 100,000 cm.',
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

            // Log activity
            $this->activityLogger->logUpdate(
                \App\Models\ActivityLog::MODULE_INVENTORY,
                $painting,
                [],
                "Cập nhật tranh: {$painting->code} - {$painting->name}"
            );

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
        // Kiểm tra xem tranh có đang được sử dụng trong phiếu chờ duyệt không
        $pendingSales = \App\Models\SaleItem::where('painting_id', $id)
            ->whereHas('sale', function ($q) {
                $q->where('sale_status', 'pending');
            })
            ->with('sale')
            ->get();

        if ($pendingSales->isNotEmpty()) {
            $invoiceCodes = $pendingSales->pluck('sale.invoice_code')->unique()->implode(', ');
            return redirect()->route('inventory.index')
                ->with('error', "Không thể xóa tranh đang được sử dụng trong phiếu chờ duyệt: {$invoiceCodes}");
        }

        // Log activity before deletion
        $paintingData = [
            'code' => $painting->code,
            'name' => $painting->name,
            'artist' => $painting->artist,
        ];
        $this->activityLogger->logDelete(
            \App\Models\ActivityLog::MODULE_INVENTORY,
            $painting,
            $paintingData,
            "Xóa tranh: {$painting->code} - {$painting->name}"
        );

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

        // Log activity
        $this->activityLogger->logUpdate(
            \App\Models\ActivityLog::MODULE_INVENTORY,
            $supply,
            [],
            "Cập nhật vật tư: {$supply->code} - {$supply->name}"
        );

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
        // Kiểm tra xem vật tư có đang được sử dụng trong phiếu chờ duyệt không
        $pendingSales = \App\Models\SaleItem::where('supply_id', $id)
            ->whereHas('sale', function ($q) {
                $q->where('sale_status', 'pending');
            })
            ->with('sale')
            ->get();

        if ($pendingSales->isNotEmpty()) {
            $invoiceCodes = $pendingSales->pluck('sale.invoice_code')->unique()->implode(', ');
            return redirect()->route('inventory.index')
                ->with('error', "Không thể xóa vật tư đang được sử dụng trong phiếu chờ duyệt: {$invoiceCodes}");
        }

        // Log activity before deletion
        $supplyData = [
            'code' => $supply->code,
            'name' => $supply->name,
            'type' => $supply->type,
        ];
        $this->activityLogger->logDelete(
            \App\Models\ActivityLog::MODULE_INVENTORY,
            $supply,
            $supplyData,
            "Xóa vật tư: {$supply->code} - {$supply->name}"
        );

        if ($supply->image) {
            Storage::disk('public')->delete($supply->image);
        }
        $supply->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Đã xóa vật tư');
    }

    public function bulkDelete(Request $request)
    {
        $items = json_decode($request->input('items'), true);

        if (empty($items)) {
            return redirect()->route('inventory.index')
                ->with('error', 'Không có mục nào được chọn để xóa');
        }

        $deletedPaintings = 0;
        $deletedSupplies = 0;
        $errors = [];

        foreach ($items as $item) {
            $id = $item['id'];
            $type = $item['type'];

            try {
                if ($type === 'painting') {
                    $painting = Painting::find($id);
                    if ($painting) {
                        // Không cho xóa tranh đã bán
                        if ($painting->status !== 'in_stock') {
                            $errors[] = "Tranh {$painting->code} đã bán, không thể xóa";
                            continue;
                        }
                        // Kiểm tra phiếu chờ duyệt
                        $pendingSales = \App\Models\SaleItem::where('painting_id', $id)
                            ->whereHas('sale', function ($q) {
                                $q->where('sale_status', 'pending');
                            })
                            ->exists();

                        if ($pendingSales) {
                            $errors[] = "Tranh {$painting->code} đang trong phiếu chờ duyệt";
                            continue;
                        }
                        // Xóa ảnh nếu có
                        if ($painting->image) {
                            Storage::disk('public')->delete($painting->image);
                        }
                        $painting->delete();
                        $deletedPaintings++;
                    }
                } elseif ($type === 'supply') {
                    $supply = Supply::find($id);
                    if ($supply) {
                        // Không cho xóa vật tư đã sử dụng hết
                        if ($supply->quantity <= 0) {
                            $errors[] = "Vật tư {$supply->code} đã sử dụng hết, không thể xóa";
                            continue;
                        }
                        // Kiểm tra phiếu chờ duyệt
                        $pendingSales = \App\Models\SaleItem::where('supply_id', $id)
                            ->whereHas('sale', function ($q) {
                                $q->where('sale_status', 'pending');
                            })
                            ->exists();

                        if ($pendingSales) {
                            $errors[] = "Vật tư {$supply->code} đang trong phiếu chờ duyệt";
                            continue;
                        }
                        // Xóa ảnh nếu có
                        if ($supply->image) {
                            Storage::disk('public')->delete($supply->image);
                        }
                        $supply->delete();
                        $deletedSupplies++;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Bulk delete error', ['item' => $item, 'error' => $e->getMessage()]);
                $errors[] = "Lỗi khi xóa {$type} ID {$id}";
            }
        }

        // Log bulk delete activity
        if ($deletedPaintings > 0 || $deletedSupplies > 0) {
            $totalDeleted = $deletedPaintings + $deletedSupplies;
            $description = "Xóa hàng loạt {$totalDeleted} sản phẩm";
            if ($deletedPaintings > 0 && $deletedSupplies > 0) {
                $description .= " ({$deletedPaintings} tranh, {$deletedSupplies} vật tư)";
            } elseif ($deletedPaintings > 0) {
                $description .= " ({$deletedPaintings} tranh)";
            } else {
                $description .= " ({$deletedSupplies} vật tư)";
            }
            
            $this->activityLogger->log(
                \App\Models\ActivityLog::TYPE_DELETE,
                \App\Models\ActivityLog::MODULE_INVENTORY,
                null,
                [
                    'deleted_paintings' => $deletedPaintings,
                    'deleted_supplies' => $deletedSupplies,
                    'total_deleted' => $totalDeleted,
                ],
                $description
            );
        }

        $totalDeleted = $deletedPaintings + $deletedSupplies;
        $message = "Đã xóa {$totalDeleted} mục";
        if ($deletedPaintings > 0) {
            $message .= " ({$deletedPaintings} tranh";
        }
        if ($deletedSupplies > 0) {
            $message .= $deletedPaintings > 0 ? ", {$deletedSupplies} vật tư)" : " ({$deletedSupplies} vật tư)";
        } else if ($deletedPaintings > 0) {
            $message .= ")";
        }

        if (!empty($errors)) {
            return redirect()->route('inventory.index')
                ->with('warning', $message . '. Một số mục không thể xóa: ' . implode('; ', $errors));
        }

        return redirect()->route('inventory.index')
            ->with('success', $message);
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
            'file' => 'required|mimes:xlsx,xls|max:51200', // Tăng lên 50MB
        ], [
            'file.required' => 'Vui lòng chọn file Excel',
            'file.mimes' => 'File phải có định dạng .xlsx hoặc .xls',
            'file.max' => 'File không được vượt quá 50MB',
        ]);

        // Validate image count
        if ($request->hasFile('images')) {
            $imageCount = count($request->file('images'));
            if ($imageCount > 200) {
                return redirect()->back()
                    ->withErrors(['images' => "Đã chọn {$imageCount} ảnh, vượt quá giới hạn 200 ảnh. Vui lòng chọn lại hoặc chia nhỏ thành nhiều lần import."])
                    ->withInput();
            }
        }

        try {
            // Tăng timeout và memory limit cho file lớn
            set_time_limit(600); // 10 phút
            ini_set('memory_limit', '1024M'); // 1GB

            Log::info('Starting painting import', [
                'file_size' => $request->file('file')->getSize(),
                'file_name' => $request->file('file')->getClientOriginalName()
            ]);

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
                    // Support flexible matching: exact match or starts with code
                    $uploadedImages[$nameWithoutExt] = $filename;

                    // Also extract potential code from filename
                    // Example: "BHH 742_79x109cm_2024-5k_resize.jpg" → "BHH 742"
                    // Pattern: Extract text before first underscore or dash
                    if (preg_match('/^([^_\-]+)/', $nameWithoutExt, $matches)) {
                        $potentialCode = trim($matches[1]);
                        if ($potentialCode !== $nameWithoutExt) {
                            $uploadedImages[$potentialCode] = $filename;
                            Log::info('Extracted code from filename', [
                                'original' => $nameWithoutExt,
                                'extracted_code' => $potentialCode,
                                'filename' => $filename
                            ]);
                        }
                    }

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
                Log::info('Loading Excel file to extract images');
                $spreadsheet = IOFactory::load($request->file('file')->getPathname());
                $worksheet = $spreadsheet->getActiveSheet();

                Log::info('Excel loaded, getting drawings');
                $drawings = $worksheet->getDrawingCollection();
                Log::info('Found drawings', ['count' => count($drawings)]);

                foreach ($drawings as $drawing) {
                    try {
                        $coordinates = $drawing->getCoordinates();
                        if (strpos($coordinates, ':') !== false) {
                            $coordinates = explode(':', $coordinates)[0];
                        }
                        $row = (int) preg_replace('/[^0-9]/', '', $coordinates);

                        if ($row <= 1)
                            continue;

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
                            Log::info('Extracted image from Excel', ['row' => $row, 'file' => $uniqueName]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error extracting single image from Excel', [
                            'row' => $row ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                        // Continue với images khác
                    }
                }

                Log::info('Finished extracting images', ['total' => count($excelImages)]);
            } catch (\Exception $e) {
                Log::error('Error extracting excel images', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Không throw exception, tiếp tục import mà không có embedded images
            }

            // Use new import class that handles images better
            Log::info('Starting Excel import process');
            $import = new PaintingImportWithImages($uploadedImages, $excelImages);
            Excel::import($import, $request->file('file'));

            $message = "Import thành công {$import->getImportedCount()} tranh";
            if ($import->getSkippedCount() > 0) {
                $message .= ", bỏ qua {$import->getSkippedCount()} dòng";
            }

            // Log activity
            $this->activityLogger->log(
                \App\Models\ActivityLog::TYPE_CREATE,
                \App\Models\ActivityLog::MODULE_INVENTORY,
                null,
                [
                    'imported_count' => $import->getImportedCount(),
                    'skipped_count' => $import->getSkippedCount(),
                    'type' => 'painting',
                ],
                "Import {$import->getImportedCount()} tranh từ Excel"
            );

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
            'file' => 'required|mimes:xlsx,xls|max:51200', // Tăng lên 50MB
        ], [
            'file.required' => 'Vui lòng chọn file Excel',
            'file.mimes' => 'File phải có định dạng .xlsx hoặc .xls',
            'file.max' => 'File không được vượt quá 50MB',
        ]);

        // Validate image count
        if ($request->hasFile('images')) {
            $imageCount = count($request->file('images'));
            if ($imageCount > 200) {
                return redirect()->back()
                    ->withErrors(['images' => "Đã chọn {$imageCount} ảnh, vượt quá giới hạn 200 ảnh. Vui lòng chọn lại hoặc chia nhỏ thành nhiều lần import."])
                    ->withInput();
            }
        }

        try {
            // Tăng timeout và memory limit cho file lớn
            set_time_limit(600); // 10 phút
            ini_set('memory_limit', '1024M'); // 1GB

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

                    // Also extract potential code from filename
                    if (preg_match('/^([^_\-]+)/', $nameWithoutExt, $matches)) {
                        $potentialCode = trim($matches[1]);
                        if ($potentialCode !== $nameWithoutExt) {
                            $uploadedImages[$potentialCode] = $filename;
                            Log::info('Extracted supply code from filename', [
                                'original' => $nameWithoutExt,
                                'extracted_code' => $potentialCode
                            ]);
                        }
                    }

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

            // Log activity
            $this->activityLogger->log(
                \App\Models\ActivityLog::TYPE_CREATE,
                \App\Models\ActivityLog::MODULE_INVENTORY,
                null,
                [
                    'imported_count' => $import->getImportedCount(),
                    'updated_count' => $import->getUpdatedCount(),
                    'skipped_count' => $import->getSkippedCount(),
                    'type' => 'supply',
                ],
                "Import {$import->getImportedCount()} vật tư từ Excel"
            );

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
