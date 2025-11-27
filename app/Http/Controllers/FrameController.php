<?php

namespace App\Http\Controllers;

use App\Models\Frame;
use App\Models\FrameItem;
use App\Models\Supply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FrameController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');

        // Check permissions
        $canSearch = true;
        $canFilterStatus = true;
        
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->email !== 'admin@example.com') {
            $role = \Illuminate\Support\Facades\Auth::user()->role;
            if ($role) {
                $perm = $role->getModulePermissions('frames');
                if ($perm) {
                    $canSearch = $perm->can_search ?? true;
                    $canFilterStatus = $perm->can_filter_by_status ?? true;
                }
            }
        }

        $query = Frame::with('items.supply');

        // Apply search if allowed
        if ($canSearch && $search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply status filter if allowed
        if ($canFilterStatus && $status) {
            $query->where('status', $status);
        }

        $frames = $query->latest()->paginate(20)->appends($request->query());
        
        return view('frames.index', compact('frames', 'search', 'status'));
    }

    public function create()
    {
        $supplies = Supply::where('type', 'frame')
            ->where('tree_count', '>', 0)
            ->orderBy('name')
            ->get();
        
        return view('frames.create', compact('supplies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'frame_length' => 'required|numeric|min:0',
            'frame_width' => 'required|numeric|min:0',
            'corner_deduction' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'cost_price_usd' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
        ]);

        DB::beginTransaction();
        try {
            // Tính chu vi khung
            $perimeter = 2 * ($validated['frame_length'] + $validated['frame_width']);
            
            // Lấy khấu trừ góc xéo từ input người dùng
            $cornerDeduction = $validated['corner_deduction'];
            
            // Tổng chiều dài cây cần
            $totalWoodNeeded = $perimeter + $cornerDeduction;

            // Tạo khung
            $frame = Frame::create([
                'name' => $validated['name'],
                'frame_length' => $validated['frame_length'],
                'frame_width' => $validated['frame_width'],
                'perimeter' => $perimeter,
                'corner_deduction' => $cornerDeduction,
                'total_wood_needed' => $totalWoodNeeded,
                'cost_price' => $validated['cost_price'],
                'cost_price_usd' => $validated['cost_price_usd'] ?? 0,
                'notes' => $validated['notes'],
            ]);

            // Thêm các items và cập nhật kho
            foreach ($validated['items'] as $itemData) {
                $supply = Supply::findOrFail($itemData['supply_id']);
                
                // Tính chiều dài cần cho loại cây này (chia đều tổng cây cần cho các loại cây)
                $woodNeededForThisSupply = $totalWoodNeeded / count($validated['items']);
                
                // Tính số cây cần dùng
                $treeQuantity = ceil($woodNeededForThisSupply / $supply->quantity);
                
                // Chiều dài thực tế cắt từ mỗi cây
                $lengthPerTree = $woodNeededForThisSupply / $treeQuantity;
                
                // Kiểm tra đủ số cây không
                if ($treeQuantity > $supply->tree_count) {
                    throw new \Exception("Cây {$supply->name} không đủ số lượng. Còn: {$supply->tree_count} cây, cần: {$treeQuantity} cây");
                }
                
                // Kiểm tra chiều dài mỗi cây
                if ($lengthPerTree > $supply->quantity) {
                    throw new \Exception("Cây {$supply->name} không đủ dài. Chiều dài mỗi cây: {$supply->quantity} cm, cần: " . round($lengthPerTree, 2) . " cm");
                }

                // Tạo frame item
                FrameItem::create([
                    'frame_id' => $frame->id,
                    'supply_id' => $itemData['supply_id'],
                    'wood_width' => null,
                    'tree_quantity' => $treeQuantity,
                    'length_per_tree' => $lengthPerTree,
                    'total_length' => $woodNeededForThisSupply,
                    'use_whole_trees' => false,
                ]);

                // Cập nhật kho: Trừ số cây đã dùng
                $supply->decrement('tree_count', $treeQuantity);
                
                // Nếu cắt cây (chiều dài cần < chiều dài mỗi cây trong kho)
                // Tự động tạo record mới cho phần dư
                if ($lengthPerTree < $supply->quantity) {
                    $remainingLength = $supply->quantity - $lengthPerTree;
                    
                    // Tìm hoặc tạo record cho phần dư
                    $existingRemaining = Supply::where('name', $supply->name)
                        ->where('type', $supply->type)
                        ->where('quantity', $remainingLength)
                        ->first();
                    
                    if ($existingRemaining) {
                        // Đã có record với chiều dài này, cộng thêm số cây
                        $existingRemaining->increment('tree_count', $treeQuantity);
                    } else {
                        // Tạo record mới cho phần dư
                        Supply::create([
                            'code' => $supply->code . '-' . round($remainingLength, 2) . 'cm',
                            'name' => $supply->name,
                            'type' => $supply->type,
                            'unit' => $supply->unit,
                            'quantity' => $remainingLength,
                            'tree_count' => $treeQuantity,
                            'notes' => "Phần dư " . round($remainingLength, 2) . "cm sau khi cắt từ {$supply->code} ({$supply->quantity}cm)",
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('frames.index')->with('success', 'Tạo khung thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Frame $frame)
    {
        $frame->load('items.supply');
        return view('frames.show', compact('frame'));
    }

    public function edit(Frame $frame)
    {
        // Không cho phép sửa khung vì logic phức tạp (đã tách cây, tạo phần dư)
        return redirect()->route('frames.index')
            ->with('error', 'Không thể sửa khung tranh. Vui lòng xóa và tạo lại nếu cần thay đổi.');
    }

    public function update(Request $request, Frame $frame)
    {
        // Không cho phép cập nhật khung
        return redirect()->route('frames.index')
            ->with('error', 'Không thể cập nhật khung tranh. Vui lòng xóa và tạo lại nếu cần thay đổi.');
        
        /* Logic cũ - đã vô hiệu hóa
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cost_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:frame_items,id',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.tree_quantity' => 'required|integer|min:1',
            'items.*.length_per_tree' => 'required|numeric|min:0',
            'items.*.use_whole_trees' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Hoàn trả vật tư cũ về kho
            foreach ($frame->items as $oldItem) {
                $originalSupply = $oldItem->supply;
                $lengthPerTree = $oldItem->length_per_tree;
                $treeQuantity = $oldItem->tree_quantity;
                
                // Tìm record với chiều dài tương ứng
                $existingSupply = Supply::where('name', $originalSupply->name)
                    ->where('type', $originalSupply->type)
                    ->where('quantity', $lengthPerTree)
                    ->first();
                
                if ($existingSupply) {
                    // Đã có record với chiều dài này, cộng thêm số cây
                    $existingSupply->increment('tree_count', $treeQuantity);
                } else {
                    // Tạo record mới để hoàn trả
                    Supply::create([
                        'code' => $originalSupply->code . '-' . $lengthPerTree . 'cm-returned',
                        'name' => $originalSupply->name,
                        'type' => $originalSupply->type,
                        'unit' => $originalSupply->unit,
                        'quantity' => $lengthPerTree,
                        'tree_count' => $treeQuantity,
                        'notes' => "Hoàn trả từ cập nhật khung {$frame->name}",
                    ]);
                }
            }

            // Xóa items cũ
            $frame->items()->delete();

            // Cập nhật thông tin khung
            $frame->update([
                'name' => $validated['name'],
                'cost_price' => $validated['cost_price'],
                'notes' => $validated['notes'],
            ]);

            // Thêm items mới và trừ kho
            foreach ($validated['items'] as $itemData) {
                $supply = Supply::findOrFail($itemData['supply_id']);
                $totalLength = $itemData['tree_quantity'] * $itemData['length_per_tree'];
                
                if ($totalLength > $supply->quantity) {
                    throw new \Exception("Cây {$supply->name} không đủ chiều dài. Còn: {$supply->quantity} cm, cần: {$totalLength} cm");
                }

                if (isset($itemData['use_whole_trees']) && $itemData['use_whole_trees']) {
                    if ($itemData['tree_quantity'] > $supply->tree_count) {
                        throw new \Exception("Cây {$supply->name} không đủ số lượng. Còn: {$supply->tree_count} cây, cần: {$itemData['tree_quantity']} cây");
                    }
                }

                FrameItem::create([
                    'frame_id' => $frame->id,
                    'supply_id' => $itemData['supply_id'],
                    'tree_quantity' => $itemData['tree_quantity'],
                    'length_per_tree' => $itemData['length_per_tree'],
                    'total_length' => $totalLength,
                    'use_whole_trees' => $itemData['use_whole_trees'] ?? false,
                ]);

                // Cập nhật kho: Chỉ trừ số cây đã dùng
                $supply->decrement('tree_count', $itemData['tree_quantity']);
                
                // Nếu cắt cây (chiều dài cần < chiều dài mỗi cây trong kho)
                // Tự động tạo record mới cho phần dư
                if ($itemData['length_per_tree'] < $supply->quantity) {
                    $remainingLength = $supply->quantity - $itemData['length_per_tree'];
                    
                    // Tìm hoặc tạo record cho phần dư
                    $existingRemaining = Supply::where('name', $supply->name)
                        ->where('type', $supply->type)
                        ->where('quantity', $remainingLength)
                        ->first();
                    
                    if ($existingRemaining) {
                        // Đã có record với chiều dài này, cộng thêm số cây
                        $existingRemaining->increment('tree_count', $itemData['tree_quantity']);
                    } else {
                        // Tạo record mới cho phần dư
                        Supply::create([
                            'code' => $supply->code . '-' . $remainingLength . 'cm',
                            'name' => $supply->name,
                            'type' => $supply->type,
                            'unit' => $supply->unit,
                            'quantity' => $remainingLength,
                            'tree_count' => $itemData['tree_quantity'],
                            'notes' => "Phần dư {$remainingLength}cm sau khi cắt từ {$supply->code} ({$supply->quantity}cm)",
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('frames.index')->with('success', 'Cập nhật khung thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
        */
    }

    public function destroy(Frame $frame)
    {
        // Không cho xóa khung đã bán
        if ($frame->status === 'sold') {
            return redirect()->route('frames.index')
                ->with('error', 'Không thể xóa khung đã bán');
        }
        
        DB::beginTransaction();
        try {
            // Hoàn trả vật tư về kho
            foreach ($frame->items as $item) {
                $originalSupply = $item->supply;
                $lengthPerTree = $item->length_per_tree;
                $treeQuantity = $item->tree_quantity;
                
                // Tìm record với chiều dài tương ứng
                $existingSupply = Supply::where('name', $originalSupply->name)
                    ->where('type', $originalSupply->type)
                    ->where('quantity', $lengthPerTree)
                    ->first();
                
                if ($existingSupply) {
                    // Đã có record với chiều dài này, cộng thêm số cây
                    $existingSupply->increment('tree_count', $treeQuantity);
                } else {
                    // Tạo record mới để hoàn trả
                    Supply::create([
                        'code' => $originalSupply->code . '-' . $lengthPerTree . 'cm-returned',
                        'name' => $originalSupply->name,
                        'type' => $originalSupply->type,
                        'unit' => $originalSupply->unit,
                        'quantity' => $lengthPerTree,
                        'tree_count' => $treeQuantity,
                        'notes' => "Hoàn trả từ khung {$frame->name}",
                    ]);
                }
            }

            $frame->delete();
            DB::commit();
            return redirect()->route('frames.index')->with('success', 'Xóa khung thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    public function getSupplyInfo($id)
    {
        $supply = Supply::findOrFail($id);
        return response()->json([
            'quantity' => $supply->quantity,
            'tree_count' => $supply->tree_count,
            'unit' => $supply->unit,
            'name' => $supply->name,
        ]);
    }
    public function getFrameJson($id)
    {
        $frame = Frame::findOrFail($id);
        return response()->json([
            'id' => $frame->id,
            'name' => $frame->name,
            'cost_price' => $frame->cost_price,
            'cost_price_usd' => $frame->cost_price_usd,
            'notes' => $frame->notes,
        ]);
    }
}
