<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete notifications older than 90 days';

    /**
     * The notification service instance.
     *
     * @var NotificationService
     */
    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Starting cleanup of old notifications (older than 90 days)...');
            
            $deletedCount = $this->notificationService->cleanupOldNotifications();
            
            if ($deletedCount === 0) {
                $this->info('No old notifications to clean up.');
                return 0;
            }
            
            $this->info("Deleted {$deletedCount} old notifications.");
            $this->info('Cleanup completed successfully!');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error during cleanup: ' . $e->getMessage());
            Log::error('Notification cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
