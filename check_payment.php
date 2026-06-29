<?php
// Cách dùng:
//   Bước 1 - Kiểm tra (chỉ xem, không xóa):
//     php check_payment.php
//
//   Bước 2 - Xóa sau khi đã xác nhận đúng:
//     php check_payment.php delete

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Payment;
use App\Models\Sale;

$doDelete = isset($argv[1]) && $argv[1] === 'delete';

echo "=== KIỂM TRA PAYMENT HÓA ĐƠN A0108062026 ===\n\n";

$payments = Payment::whereHas('sale', function ($q) {
        $q->where('invoice_code', 'A0108062026');
    })
    ->where('payment_vnd', 107171856)
    ->orderBy('id')
    ->get(['id', 'sale_id', 'amount', 'payment_vnd', 'payment_exchange_rate', 'payment_date', 'notes', 'created_at']);

if ($payments->isEmpty()) {
    echo "Không tìm thấy payment nào với payment_vnd = 107,171,856d\n";
    exit;
}

echo "Tìm thấy " . $payments->count() . " payment(s):\n";
echo str_repeat('-', 70) . "\n";
foreach ($payments as $p) {
    $tag = ($p->payment_exchange_rate == 26134) ? " <== SE BI XOA" : " <== GIU LAI";
    echo "  ID              : " . $p->id . $tag . "\n";
    echo "  Amount (VND)    : " . number_format($p->payment_vnd) . "d\n";
    echo "  Ty gia          : " . number_format($p->payment_exchange_rate) . "\n";
    echo "  Ngay thanh toan : " . $p->payment_date . "\n";
    echo "  Ghi chu         : " . $p->notes . "\n";
    echo str_repeat('-', 70) . "\n";
}

// Xác định payment SAI: tỷ giá 26134
$wrongPayment = $payments->where('payment_exchange_rate', 26134)->first();

if (!$wrongPayment) {
    echo "\nKhong tim thay payment ty gia 26,134. Co the da xoa roi.\n";
    exit;
}

echo "\n=== PAYMENT SE BI XOA ===\n";
echo "  ID      : " . $wrongPayment->id . "\n";
echo "  VND     : " . number_format($wrongPayment->payment_vnd) . "d\n";
echo "  Ty gia  : " . number_format($wrongPayment->payment_exchange_rate) . "\n";
echo "  Tao luc : " . $wrongPayment->created_at . "\n\n";

if (!$doDelete) {
    echo ">>> De xoa, chay lai lenh: php check_payment.php delete\n";
    exit;
}

// === THỰC HIỆN XÓA ===
$saleId = $wrongPayment->sale_id;
$deletedId = $wrongPayment->id;
$wrongPayment->delete();

$sale = Sale::find($saleId);
if ($sale) {
    $sale->refresh();
    $sale->updatePaymentStatus();
    echo "=== XOA THANH CONG ===\n";
    echo "Da xoa payment ID=" . $deletedId . "\n";
    echo "Hoa don         : " . $sale->invoice_code . "\n";
    echo "Tong da tra USD : $" . number_format($sale->paid_usd, 2) . "\n";
    echo "Trang thai TT   : " . $sale->payment_status . "\n";
    echo "\nXong! Co the xoa file nay: rm check_payment.php\n";
}
