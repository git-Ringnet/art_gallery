<?php

use App\Models\Payment;

// Tìm thanh toán của đơn B0119062026 vào ngày 29/06/2026
$payment = Payment::whereHas('sale', function ($query) {
    $query->where('invoice_code', 'B0119062026');
})->whereDate('payment_date', '2026-06-29')->first();

if ($payment) {
    echo "Tìm thấy thanh toán ID: " . $payment->id . "\n";
    echo "Ngày thanh toán cũ: " . $payment->payment_date . "\n";
    
    // Cập nhật ngày thanh toán thành 26/06/2026 13:27:35 (giữ nguyên giờ cũ)
    $payment->payment_date = '2026-06-26 13:27:35';
    
    // Đồng thời cập nhật năm (year) của thanh toán thành 2026 cho đúng thực tế
    $payment->year = 2026;
    
    $payment->save();
    
    echo "Cập nhật thành công!\n";
    echo "Ngày thanh toán mới: " . $payment->payment_date . "\n";
} else {
    echo "Không tìm thấy thanh toán của đơn B0119062026 trong ngày 29/06/2026.\n";
}
