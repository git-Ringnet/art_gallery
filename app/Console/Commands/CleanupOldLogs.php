<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:cleanup {--days= : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old activity logs based on retention period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $retentionDays = $this->option('days') ?? config('activitylog.retention_days', 365);
            
            $this->info("Starting cleanup of activity logs older than {$retentionDays} days...");
            
            $cutoffDate = now()->subDays($retentionDays);
            
            // Count logs to be deleted
            $totalCount = ActivityLog::where('created_at', '<', $cutoffDate)
                ->where('is_important', false)
                ->count();
            
            if ($totalCount === 0) {
                $this->info('No old logs to clean up.');
                return 0;
            }
            
            $this->info("Found {$totalCount} logs to delete...");
            
            // Delete old logs (preserve important and suspicious ones)
            $deletedCount = ActivityLog::where('created_at', '<', $cutoffDate)
                ->where('is_important', false)
                ->delete();
            
            $preservedCount = $totalCount - $deletedCount;
            
            $this->info("Deleted {$deletedCount} old activity logs.");
            
            if ($preservedCount > 0) {
                $this->info("Preserved {$preservedCount} important/suspicious logs.");
            }
            
            // Log the cleanup operation
            Log::info('Activity log cleanup completed', [
                'retention_days' => $retentionDays,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
                'deleted_count' => $deletedCount,
                'preserved_count' => $preservedCount,
            ]);
            
            $this->info('Cleanup completed successfully!');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error during cleanup: ' . $e->getMessage());
            Log::error('Activity log cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
