<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// SCHEDULED TASKS - YEAR-END AUTOMATION
// ============================================

// 1. Backup tự động cuối năm (31/12 lúc 23:00)
Schedule::command('year:backup --description="Backup tự động cuối năm"')
    ->yearlyOn(12, 31, '23:00')
    ->timezone('Asia/Ho_Chi_Minh')
    ->onSuccess(function () {
        Log::info('Year-end backup scheduled task completed successfully');
    })
    ->onFailure(function () {
        Log::error('Year-end backup scheduled task failed');
    });

// 2. Backup hàng tuần (Chủ nhật lúc 02:00)
Schedule::command('year:backup --description="Backup tự động hàng tuần"')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->timezone('Asia/Ho_Chi_Minh');

// 3. Backup hàng tháng (Ngày 1 lúc 01:00)
Schedule::command('year:backup --description="Backup tự động đầu tháng"')
    ->monthlyOn(1, '01:00')
    ->timezone('Asia/Ho_Chi_Minh');

// LƯU Ý: Cleanup phải chạy THỦ CÔNG vì nguy hiểm!
// Không tự động schedule cleanup
// Chạy thủ công: php artisan year:cleanup --force
