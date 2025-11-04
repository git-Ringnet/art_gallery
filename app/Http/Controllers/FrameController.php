<?php

namespace App\Http\Controllers;

use App\Models\Frame;
use App\Models\FrameItem;
use App\Models\Supply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FrameController extends Controller
{
    public function index()
    {
        $frames = Frame::with('items.supply')->latest()->paginate(20);
        return view('frames.index', compact('frames'));
    }

    public function create()
    {
        $supplies = Supply::where('type', 'frame')
            ->where(function($q) {
                $q->where('quantity', '>', 0)
                  ->orWhere('tree_count', '>', 0);
            })
            ->orderBy('name')
            ->get();
        
        return view('frames.create', compact('supplies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cost_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.tree_quantity' => 'required|integer|min:1',
            'items.*.length_per_tree' => 'required|numeric|min:0',
            'items.*.use_whole_trees' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Tạo khung
            $frame = Frame::create([
                'name' => $validated['name'],
                'cost_price' => $validated['cost_price'],
                'notes' => $validated['notes'],
            ]);

            // Thêm các items và cập nhật kho
            foreach ($validated['items'] as $itemData) {
                $supply = Supply::findOrFail($itemData['supply_id']);
                $totalLength = $itemData['tree_quantity'] * $itemData['length_per_tree'];
                
                // Kiểm tra đủ chiều dài không
                if ($totalLength > $supply->quantity) {
                    throw new \Exception("Cây {$supply->name} không đủ chiều dài. Còn: {$supply->quantity} cm, cần: {$totalLength} cm");
                }

                // Kiểm tra đủ số cây không (nếu chọn dùng nguyên cây)
                if (isset($itemData['use_whole_trees']) && $itemData['use_whole_trees']) {
                    if ($itemData['tree_quantity'] > $supply->tree_count) {
                        throw new \Exception("Cây {$supply->name} không đủ số lượng. Còn: {$supply->tree_count} cây, cần: {$itemData['tree_quantity']} cây");
                    }
                }

                // Tạo frame item
                FrameItem::create([
                    'frame_id' => $frame->id,
                    'supply_id' => $itemData['supply_id'],
                    'tree_quantity' => $itemData['tree_quantity'],
                    'length_per_tree' => $itemData['length_per_tree'],
                    'total_length' => $totalLength,
                    'use_whole_trees' => $itemData['use_whole_trees'] ?? false,
                ]);

                // Cập nhật kho
                $supply->decrement('quantity', $totalLength);
                if (isset($itemData['use_whole_trees']) && $itemData['use_whole_trees']) {
                    $supply->decrement('tree_count', $itemData['tree_quantity']);
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
        $frame->load('items.supply');
        $supplies = Supply::where('type', 'frame')->orderBy('name')->get();
        return view('frames.edit', compact('frame', 'supplies'));
    }

    public function update(Request $request, Frame $frame)
    {
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
                $supply = $oldItem->supply;
                $supply->increment('quantity', (float) $oldItem->total_length);
                if ($oldItem->use_whole_trees) {
                    $supply->increment('tree_count', $oldItem->tree_quantity);
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

                $supply->decrement('quantity', $totalLength);
                if (isset($itemData['use_whole_trees']) && $itemData['use_whole_trees']) {
                    $supply->decrement('tree_count', $itemData['tree_quantity']);
                }
            }

            DB::commit();
            return redirect()->route('frames.index')->with('success', 'Cập nhật khung thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Frame $frame)
    {
        DB::beginTransaction();
        try {
            // Hoàn trả vật tư về kho
            foreach ($frame->items as $item) {
                $supply = $item->supply;
                $supply->increment('quantity', (float) $item->total_length);
                if ($item->use_whole_trees) {
                    $supply->increment('tree_count', $item->tree_quantity);
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
}
