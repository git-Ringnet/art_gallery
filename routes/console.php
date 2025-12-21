<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// SCHEDULED TASKS - YEAR-END AUTOMATION
// ============================================

// QUY TRÌNH CHUYỂN NĂM TỰ ĐỘNG
// Chạy lúc 00:05 ngày 1/1 hàng năm
// Thực hiện: Export năm cũ → Cleanup → Chuẩn bị năm mới
Schedule::command('year:end-process --force')
    ->yearlyOn(1, 1, '00:05')  // Ngày 1 tháng 1, lúc 00:05
    ->timezone('Asia/Ho_Chi_Minh')
    ->before(function () {
        Log::info('=== BẮT ĐẦU QUY TRÌNH CHUYỂN NĂM TỰ ĐỘNG ===');
    })
    ->onSuccess(function () {
        Log::info('=== QUY TRÌNH CHUYỂN NĂM HOÀN THÀNH ===');
    })
    ->onFailure(function () {
        Log::error('=== QUY TRÌNH CHUYỂN NĂM THẤT BẠI - CẦN KIỂM TRA ===');
    });

// Backup tự động cuối năm (31/12 lúc 23:00) - Backup ĐẦY ĐỦ (SQL + ảnh) trước khi chuyển năm
Schedule::call(function () {
    $year = date('Y');
    Log::info("Bắt đầu backup cuối năm {$year} (SQL + ảnh)...");
    Artisan::call('year:export', [
        'year' => $year,
        '--include-images' => true,
    ]);
    Log::info("Backup cuối năm {$year} hoàn thành!");
})
    ->yearlyOn(12, 31, '23:00')
    ->timezone('Asia/Ho_Chi_Minh')
    ->name('year-end-full-backup')
    ->onSuccess(function () {
        Log::info('Year-end full backup (SQL + images) completed successfully');
    })
    ->onFailure(function () {
        Log::error('Year-end full backup failed');
    });

// Backup hàng tuần (Chủ nhật lúc 02:00) - Chỉ SQL
Schedule::command('year:backup --description="Backup tự động hàng tuần"')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->timezone('Asia/Ho_Chi_Minh');

// Backup hàng tháng (Ngày 1 lúc 03:00) - Chỉ SQL
Schedule::command('year:backup --description="Backup tự động đầu tháng"')
    ->monthlyOn(1, '03:00')
    ->timezone('Asia/Ho_Chi_Minh');
