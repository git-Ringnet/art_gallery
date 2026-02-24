<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Exception;

class ActivityLogObserver
{
    protected NotificationService $notificationService;

    /**
     * Create a new observer instance.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the ActivityLog "created" event.
     *
     * @param ActivityLog $activityLog
     * @return void
     */
    public function created(ActivityLog $activityLog): void
    {
        try {
            // Skip notification creation for activity_type in ['login', 'logout', 'view']
            if (in_array($activityLog->activity_type, ['login', 'logout', 'view'])) {
                return;
            }

            // Create notification from activity log
            $this->notificationService->createFromActivityLog($activityLog);
        } catch (Exception $e) {
            // Log error but don't break the main flow
            Log::error('ActivityLogObserver failed to create notification', [
                'activity_log_id' => $activityLog->id,
                'activity_type' => $activityLog->activity_type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Silently fail - observer errors should not break the main application flow
        }
    }
}
