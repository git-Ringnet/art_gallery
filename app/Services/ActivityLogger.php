<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity
     */
    public function log(
        string $activityType,
        string $module,
        ?Model $subject = null,
        array $properties = [],
        ?string $description = null
    ): ?ActivityLog {
        try {
            $context = $this->captureContext();
            
            $data = [
                'user_id' => $context['user_id'],
                'activity_type' => $activityType,
                'module' => $module,
                'description' => $description,
                'ip_address' => $context['ip_address'],
                'user_agent' => $context['user_agent'],
                'properties' => !empty($properties) ? $properties : null,
            ];

            // Add subject if provided
            if ($subject) {
                $data['subject_type'] = get_class($subject);
                $data['subject_id'] = $subject->id;
            }

            // Check for suspicious activity
            $isSuspicious = $this->checkSuspiciousActivity($activityType, $module);
            if ($isSuspicious) {
                $data['is_suspicious'] = true;
            }

            return ActivityLog::create($data);
        } catch (\Exception $e) {
            // Log error but don't throw exception to avoid interrupting main flow
            Log::error('ActivityLogger error: ' . $e->getMessage(), [
                'activity_type' => $activityType,
                'module' => $module,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Log user login
     */
    public function logLogin(User $user): ?ActivityLog
    {
        return $this->log(
            ActivityLog::TYPE_LOGIN,
            ActivityLog::MODULE_AUTH,
            $user,
            [],
            "Người dùng {$user->name} đăng nhập"
        );
    }

    /**
     * Log user logout
     */
    public function logLogout(User $user, ?int $sessionDuration = null): ?ActivityLog
    {
        $properties = [];
        if ($sessionDuration !== null) {
            $properties['session_duration'] = $sessionDuration;
        }

        return $this->log(
            ActivityLog::TYPE_LOGOUT,
            ActivityLog::MODULE_AUTH,
            $user,
            $properties,
            "Người dùng {$user->name} đăng xuất"
        );
    }

    /**
     * Log record creation
     */
    public function logCreate(string $module, Model $subject, ?string $description = null): ?ActivityLog
    {
        if (!$description) {
            $description = "Tạo mới " . class_basename($subject) . " #{$subject->id}";
        }

        return $this->log(
            ActivityLog::TYPE_CREATE,
            $module,
            $subject,
            [],
            $description
        );
    }

    /**
     * Log record update
     */
    public function logUpdate(string $module, Model $subject, array $changes = [], ?string $description = null): ?ActivityLog
    {
        // If no changes provided, try to detect them
        if (empty($changes) && $subject->wasChanged()) {
            $changes = $this->detectChanges($subject);
        }

        if (!$description) {
            $description = "Cập nhật " . class_basename($subject) . " #{$subject->id}";
        }

        try {
            $context = $this->captureContext();
            
            $data = [
                'user_id' => $context['user_id'],
                'activity_type' => ActivityLog::TYPE_UPDATE,
                'module' => $module,
                'description' => $description,
                'subject_type' => get_class($subject),
                'subject_id' => $subject->id,
                'ip_address' => $context['ip_address'],
                'user_agent' => $context['user_agent'],
                'changes' => !empty($changes) ? $changes : null,
            ];

            return ActivityLog::create($data);
        } catch (\Exception $e) {
            Log::error('ActivityLogger error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log record deletion
     */
    public function logDelete(string $module, Model $subject, array $deletedData = [], ?string $description = null): ?ActivityLog
    {
        if (!$description) {
            $description = "Xóa " . class_basename($subject) . " #{$subject->id}";
        }

        // Store important attributes before deletion
        if (empty($deletedData)) {
            $deletedData = $subject->toArray();
        }

        return $this->log(
            ActivityLog::TYPE_DELETE,
            $module,
            $subject,
            ['deleted_data' => $deletedData],
            $description
        );
    }

    /**
     * Log approval action
     */
    public function logApprove(string $module, Model $subject, ?string $reason = null, ?string $description = null): ?ActivityLog
    {
        if (!$description) {
            $description = "Duyệt " . class_basename($subject) . " #{$subject->id}";
        }

        $properties = [];
        if ($reason) {
            $properties['reason'] = $reason;
        }

        return $this->log(
            ActivityLog::TYPE_APPROVE,
            $module,
            $subject,
            $properties,
            $description
        );
    }

    /**
     * Log cancellation action
     */
    public function logCancel(string $module, Model $subject, ?string $reason = null, ?string $description = null): ?ActivityLog
    {
        if (!$description) {
            $description = "Hủy " . class_basename($subject) . " #{$subject->id}";
        }

        $properties = [];
        if ($reason) {
            $properties['reason'] = $reason;
        }

        return $this->log(
            ActivityLog::TYPE_CANCEL,
            $module,
            $subject,
            $properties,
            $description
        );
    }

    /**
     * Capture request context (IP, user agent, user)
     */
    private function captureContext(): array
    {
        return [
            'user_id' => Auth::id(),
            'ip_address' => Request::ip() ?? 'Unknown',
            'user_agent' => Request::userAgent() ?? 'Unknown',
        ];
    }

    /**
     * Detect changes in a model
     */
    private function detectChanges(Model $model): array
    {
        $changes = [];
        
        if ($model->wasChanged()) {
            foreach ($model->getChanges() as $key => $newValue) {
                // Skip timestamps and certain fields
                if (in_array($key, ['updated_at', 'created_at', 'remember_token'])) {
                    continue;
                }

                $oldValue = $model->getOriginal($key);
                
                // Don't log password changes (security)
                if ($key === 'password') {
                    $changes[$key] = [
                        'old' => '***',
                        'new' => '***',
                    ];
                } else {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Check for suspicious activity patterns
     */
    private function checkSuspiciousActivity(string $activityType, string $module): bool
    {
        try {
            $context = $this->captureContext();
            $ipAddress = $context['ip_address'];
            $userId = $context['user_id'];

            // Check for multiple failed login attempts
            if ($activityType === ActivityLog::TYPE_LOGIN && $module === ActivityLog::MODULE_AUTH) {
                $recentFailedLogins = ActivityLog::where('ip_address', $ipAddress)
                    ->where('activity_type', ActivityLog::TYPE_LOGIN)
                    ->where('is_suspicious', false)
                    ->where('created_at', '>', now()->subMinutes(5))
                    ->count();

                if ($recentFailedLogins >= config('activitylog.suspicious_login_attempts', 5)) {
                    return true;
                }
            }

            // Check for excessive delete operations
            if ($activityType === ActivityLog::TYPE_DELETE) {
                $recentDeletes = ActivityLog::where('user_id', $userId)
                    ->where('activity_type', ActivityLog::TYPE_DELETE)
                    ->where('created_at', '>', now()->subMinutes(10))
                    ->count();

                if ($recentDeletes >= config('activitylog.suspicious_delete_threshold', 10)) {
                    return true;
                }
            }

            // Check for new IP address
            if ($userId && $ipAddress !== 'Unknown') {
                $previousIpCount = ActivityLog::where('user_id', $userId)
                    ->where('ip_address', $ipAddress)
                    ->count();

                // If this is the first time from this IP, it's notable but not necessarily suspicious
                // We'll just log it without marking as suspicious
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error checking suspicious activity: ' . $e->getMessage());
            return false;
        }
    }
}
