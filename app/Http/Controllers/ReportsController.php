<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\ExchangeRate;
use App\Models\Showroom;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * Display daily cash collection report
     */
    public function dailyCashCollection(Request $request)
    {
        // Lấy ngày từ request hoặc mặc định là hôm nay
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $reportDate = Carbon::parse($date);
        
        // Lấy showroom_id từ request (null = all showrooms)
        $showroomId = $request->input('showroom_id');
        
        // Lấy thông tin showroom nếu được chọn
        $selectedShowroom = null;
        if ($showroomId) {
            $selectedShowroom = Showroom::find($showroomId);
        }
        
        // Lấy tất cả showrooms cho dropdown
        $showrooms = Showroom::orderBy('name')->get();
        
        // Lấy tỷ giá từ request hoặc tỷ giá hiện tại
        $exchangeRate = $request->input('exchange_rate');
        if (!$exchangeRate) {
            $currentRate = ExchangeRate::getCurrentRate();
            $exchangeRate = $currentRate ? $currentRate->rate : 25000;
        }
        
        // Query sales items trong ngày (lặp qua TRANH, không phải hóa đơn)
        // CHỈ tính các sale đã DUYỆT (completed), không tính pending/cancelled
        $itemsQuery = SaleItem::with(['sale.customer', 'sale.showroom', 'sale.payments', 'painting', 'supply', 'frame'])
            ->whereHas('sale', function($q) use ($reportDate, $showroomId) {
                $q->whereDate('sale_date', $reportDate)
                  ->where('sale_status', 'completed');  // CHỈ lấy sales đã duyệt
                if ($showroomId) {
                    $q->where('showroom_id', $showroomId);
                }
            })
            ->where('is_returned', '!=', true) // Không tính items đã trả
            ->orderBy('id')
            ->get();
        
        // Tính toán dữ liệu báo cáo - MỖI DÒNG = 1 TRANH
        $reportData = [];
        $totalDepositUsd = 0;
        $totalDepositVnd = 0;
        $totalAdjustmentUsd = 0;
        $totalAdjustmentVnd = 0;
        
        foreach ($itemsQuery as $item) {
            $sale = $item->sale;
            
            // ID Code = mã tranh hoặc supply hoặc frame
            $idCode = '';
            if ($item->painting_id) {
                $idCode = $item->painting->code ?? 'N/A';
            } elseif ($item->supply_id) {
                $idCode = $item->supply->code ?? 'SUP' . $item->supply_id;
            } elseif ($item->frame_id) {
                $idCode = 'FRAME' . $item->frame_id;
            }
            
            $rowData = [
                'invoice_code' => $sale->invoice_code,
                'id_code' => $idCode,
                'customer_name' => $sale->customer->name,
                'currency' => $item->currency,
                'deposit_usd' => 0,
                'deposit_vnd' => 0,
                'adjustment_usd' => 0,
                'adjustment_vnd' => 0,
                'collection_usd' => 0,  // Collection tính theo hóa đơn, không theo tranh
                'collection_vnd' => 0,
            ];
            
            // Deposit = Giá của tranh này (CHỈ USD hoặc VND, không cả hai)
            if ($item->currency == 'USD') {
                // Tính subtotal trước discount
                $subtotal = $item->quantity * $item->price_usd;
                $rowData['deposit_usd'] = $subtotal;
                $totalDepositUsd += $subtotal;
                
                // Adjustment = giảm giá của tranh này
                if ($item->discount_percent > 0) {
                    $discount = $subtotal * ($item->discount_percent / 100);
                    $rowData['adjustment_usd'] = -$discount;
                    $totalAdjustmentUsd += -$discount;
                }
            } else { // VND
                $subtotal = $item->quantity * $item->price_vnd;
                $rowData['deposit_vnd'] = $subtotal;
                $totalDepositVnd += $subtotal;
                
                if ($item->discount_percent > 0) {
                    $discount = $subtotal * ($item->discount_percent / 100);
                    $rowData['adjustment_vnd'] = -$discount;
                    $totalAdjustmentVnd += -$discount;
                }
            }
            
            $reportData[] = $rowData;
        }
        
        // Tính toán Collection từ payments (CHỈ từ sales đã duyệt)
        $totalCollectionUsd = 0;
        $totalCollectionVnd = 0;
        $cashCollectionVnd = 0;
        $cardCollectionVnd = 0;
        
        $paymentsQuery = Payment::with(['sale'])
            ->whereDate('payment_date', $reportDate)
            ->where('transaction_type', 'sale_payment')
            ->whereHas('sale', function($q) use ($showroomId) {
                $q->where('sale_status', 'completed');  // CHỈ collection từ sales đã duyệt
                if ($showroomId) {
                    $q->where('showroom_id', $showroomId);
                }
            })
            ->get();
            
        foreach ($paymentsQuery as $payment) {
            $totalCollectionUsd += $payment->payment_usd;
            $totalCollectionVnd += $payment->amount;
            
            if ($payment->payment_method == 'cash') {
                $cashCollectionVnd += $payment->amount;
            } elseif ($payment->payment_method == 'card') {
                $cardCollectionVnd += $payment->amount;
            } else {
                $cashCollectionVnd += $payment->amount;
            }
        }
        
        // Tính Total VND cho Deposit và Adjustment dựa trên tỷ giá
        // Deposit Total VND = (Deposit USD × Tỷ giá) + Deposit VND
        $totalDepositTotalVnd = ($totalDepositUsd * $exchangeRate) + $totalDepositVnd;
        
        // Adjustment Total VND = (Adjustment USD × Tỷ giá) + Adjustment VND
        $totalAdjustmentTotalVnd = ($totalAdjustmentUsd * $exchangeRate) + $totalAdjustmentVnd;
        
        // Grand Total VND (cho Collection) = (Collection USD × Tỷ giá) + Collection VND
        $grandTotalVnd = ($totalCollectionUsd * $exchangeRate) + $totalCollectionVnd;
        
        return view('reports.daily-cash-collection', compact(
            'reportData',
            'reportDate',
            'exchangeRate',
            'showrooms',
            'showroomId',
            'selectedShowroom',
            'totalDepositUsd',
            'totalDepositVnd',
            'totalDepositTotalVnd',
            'totalAdjustmentUsd',
            'totalAdjustmentVnd',
            'totalAdjustmentTotalVnd',
            'totalCollectionUsd',
            'totalCollectionVnd',
            'cashCollectionVnd',
            'cardCollectionVnd',
            'grandTotalVnd'
        ));
    }
    
    /**
     * Display reports index/dashboard
     */
    public function index()
    {
        return view('reports.index');
    }
}
