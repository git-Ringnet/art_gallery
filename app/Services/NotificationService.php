<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AdminNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationService
{
    /**
     * Create notification from activity log
     * 
     * @param ActivityLog $activityLog
     * @return AdminNotification
     */
    public function createFromActivityLog(ActivityLog $activityLog): AdminNotification
    {
        try {
            $severityLevel = $this->determineSeverityLevel(
                $activityLog->activity_type,
                $activityLog->module
            );

            $notification = AdminNotification::create([
                'activity_log_id' => $activityLog->id,
                'severity_level' => $severityLevel,
            ]);

            // Clear cache
            Cache::forget('admin_notifications_unread_count');

            Log::info('Admin notification created', [
                'notification_id' => $notification->id,
                'activity_log_id' => $activityLog->id,
                'severity_level' => $severityLevel,
            ]);

            return $notification;
        } catch (Exception $e) {
            Log::error('Failed to create admin notification', [
                'activity_log_id' => $activityLog->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Determine severity level based on activity type and module
     * 
     * @param string $activityType
     * @param string $module
     * @return string
     */
    public function determineSeverityLevel(string $activityType, string $module): string
    {
        // Critical: delete, cancel, bulk_delete
        if (in_array($activityType, ['delete', 'cancel', 'bulk_delete'])) {
            return AdminNotification::SEVERITY_CRITICAL;
        }

        // Warning: approve, update
        if (in_array($activityType, ['approve', 'update'])) {
            return AdminNotification::SEVERITY_WARNING;
        }

        // Info: create, import, collect_payment, complete
        return AdminNotification::SEVERITY_INFO;
    }

    /**
     * Generate link to subject based on subject_type and subject_id
     * 
     * @param ActivityLog $activityLog
     * @return string|null
     */
    public function generateLink(ActivityLog $activityLog): ?string
    {
        try {
            if (!$activityLog->subject_type || !$activityLog->subject_id) {
                return null;
            }

            $routes = [
                'App\\Models\\Sale' => 'sales.show',
                'App\\Models\\Painting' => 'paintings.show',
                'App\\Models\\Supply' => 'supplies.show',
                'App\\Models\\Frame' => 'frames.show',
                'App\\Models\\ReturnModel' => 'returns.show',
                'App\\Models\\Payment' => 'debt-payments.show',
                'App\\Models\\Customer' => 'customers.show',
                'App\\Models\\User' => 'employees.show',
                'App\\Models\\Showroom' => 'showrooms.show',
            ];

            $routeName = $routes[$activityLog->subject_type] ?? null;

            if (!$routeName) {
                Log::warning('No route mapping found for subject type', [
                    'subject_type' => $activityLog->subject_type,
                ]);
                return null;
            }

            return route($routeName, $activityLog->subject_id);
        } catch (Exception $e) {
            Log::error('Failed to generate notification link', [
                'activity_log_id' => $activityLog->id,
                'subject_type' => $activityLog->subject_type,
                'subject_id' => $activityLog->subject_id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get unread count with caching (30 seconds)
     * 
     * @return int
     */
    public function getUnreadCount(): int
    {
        try {
            return Cache::remember('admin_notifications_unread_count', 30, function () {
                return AdminNotification::unread()->count();
            });
        } catch (Exception $e) {
            Log::error('Failed to get unread notification count', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Cleanup old notifications (older than 90 days)
     * 
     * @return int Number of deleted notifications
     */
    public function cleanupOldNotifications(): int
    {
        try {
            $cutoffDate = now()->subDays(90);
            
            $deletedCount = AdminNotification::where('created_at', '<', $cutoffDate)->delete();

            Log::info('Old notifications cleaned up', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
            ]);

            return $deletedCount;
        } catch (Exception $e) {
            Log::error('Failed to cleanup old notifications', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
