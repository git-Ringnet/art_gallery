<?php

/**
 * Script để tự động sửa tất cả models kế thừa BaseModel
 * 
 * Usage: php update-models-to-basemodel.php
 */

$modelsDir = __DIR__ . '/app/Models';

// Danh sách models KHÔNG cần sửa (system models)
$excludeModels = [
    'User.php',
    'Role.php',
    'Permission.php',
    'RolePermission.php',
    'YearDatabase.php',
    'FieldPermission.php',
    'CustomField.php',
    'BaseModel.php',  // Chính nó
];

// Danh sách models cần sửa (business models)
$businessModels = [
    'Sale.php',
    'SaleItem.php',
    'Customer.php',
    'Showroom.php',
    'Painting.php',
    'Supply.php',
    'Payment.php',
    'Debt.php',
    'ExchangeRate.php',
    'InventoryTransaction.php',
    'Employee.php',
    'Return.php',
    'ReturnItem.php',
    'DatabaseExport.php',
];

echo "=== Bắt đầu cập nhật models ===\n\n";

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($businessModels as $modelFile) {
    $filePath = $modelsDir . '/' . $modelFile;
    
    if (!file_exists($filePath)) {
        echo "⚠️  SKIP: {$modelFile} - File không tồn tại\n";
        $skipped++;
        continue;
    }
    
    // Đọc nội dung file
    $content = file_get_contents($filePath);
    
    // Kiểm tra đã extends BaseModel chưa
    if (strpos($content, 'extends BaseModel') !== false) {
        echo "✓  SKIP: {$modelFile} - Đã extends BaseModel\n";
        $skipped++;
        continue;
    }
    
    // Thay thế "extends Model" thành "extends BaseModel"
    $newContent = preg_replace(
        '/class\s+(\w+)\s+extends\s+Model/',
        'class $1 extends BaseModel',
        $content
    );
    
    if ($newContent === $content) {
        echo "⚠️  SKIP: {$modelFile} - Không tìm thấy 'extends Model'\n";
        $skipped++;
        continue;
    }
    
    // Backup file gốc
    $backupPath = $filePath . '.backup';
    copy($filePath, $backupPath);
    
    // Ghi file mới
    if (file_put_contents($filePath, $newContent)) {
        echo "✅ UPDATE: {$modelFile} - Đã sửa thành extends BaseModel\n";
        $updated++;
    } else {
        echo "❌ ERROR: {$modelFile} - Không thể ghi file\n";
        $errors++;
    }
}

echo "\n=== Kết quả ===\n";
echo "✅ Đã cập nhật: {$updated} files\n";
echo "⚠️  Đã bỏ qua: {$skipped} files\n";
echo "❌ Lỗi: {$errors} files\n";

if ($updated > 0) {
    echo "\n📝 Lưu ý:\n";
    echo "- File backup được lưu với extension .backup\n";
    echo "- Hãy test kỹ trước khi xóa backup\n";
    echo "- Nếu có lỗi, restore từ backup: copy *.backup sang *.php\n";
}

echo "\n✅ Hoàn thành!\n";
