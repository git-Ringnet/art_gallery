<?php
// Chạy: php artisan tinker check_payment.php
// HOẶC: php check_payment.php (sau khi cd vào thư mục project)

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Payment;
use App\Models\Sale;

echo "=== KIỂM TRA PAYMENT HÓA ĐƠN A0108062026 ===\n\n";

$payments = Payment::whereHas('sale', function ($q) {
        $q->where('invoice_code', 'A0108062026');
    })
    ->where('payment_vnd', 107171856)
    ->orderBy('id')
    ->get(['id', 'sale_id', 'amount', 'payment_vnd', 'payment_exchange_rate', 'payment_date', 'notes', 'created_at']);

if ($payments->isEmpty()) {
    echo "Không tìm thấy payment nào với payment_vnd = 107,171,856đ\n";
    exit;
}

echo "Tìm thấy " . $payments->count() . " payment(s):\n";
echo str_repeat('-', 80) . "\n";
foreach ($payments as $p) {
    echo "  ID              : " . $p->id . "\n";
    echo "  Sale ID         : " . $p->sale_id . "\n";
    echo "  Amount (VND)    : " . number_format($p->payment_vnd) . "đ\n";
    echo "  Tỷ giá          : " . number_format($p->payment_exchange_rate) . "\n";
    echo "  Ngày thanh toán : " . $p->payment_date . "\n";
    echo "  Ghi chú         : " . $p->notes . "\n";
    echo "  Tạo lúc         : " . $p->created_at . "\n";
    echo str_repeat('-', 80) . "\n";
}

// Xác định payment SAI: tỷ giá 26134
$wrongPayment = $payments->where('payment_exchange_rate', 26134)->first();

if (!$wrongPayment) {
    echo "\nKhông tìm thấy payment với tỷ giá 26,134. Kiểm tra lại payment_exchange_rate.\n";
    echo "Các tỷ giá hiện có: " . $payments->pluck('payment_exchange_rate')->implode(', ') . "\n";
    exit;
}

echo "\n=== PAYMENT SẼ BỊ XÓA ===\n";
echo "  ID     : " . $wrongPayment->id . "\n";
echo "  VND    : " . number_format($wrongPayment->payment_vnd) . "đ\n";
echo "  Tỷ giá : " . number_format($wrongPayment->payment_exchange_rate) . "\n";
echo "  Tạo lúc: " . $wrongPayment->created_at . "\n\n";

echo "Xác nhận xóa? (yes/no): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'yes') {
    echo "Đã hủy. Không xóa gì cả.\n";
    exit;
}

// Xóa payment và cập nhật lại trạng thái hóa đơn
$sale = Sale::find($wrongPayment->sale_id);
$wrongPayment->delete();
$sale->refresh();
$sale->updatePaymentStatus();

echo "\n✅ Đã xóa payment ID=" . $wrongPayment->id . " thành công!\n";
echo "✅ Đã cập nhật lại trạng thái hóa đơn " . $sale->invoice_code . "\n";
echo "   Tổng đã trả mới: $" . number_format($sale->paid_usd, 2) . " / " . number_format($sale->paid_vnd) . "đ\n";
echo "   Trạng thái TT  : " . $sale->payment_status . "\n";
