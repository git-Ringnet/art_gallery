<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * GET /api/notifications/unread-count
     * Return unread notification count with display text
     * 
     * @return JsonResponse
     */
    public function getUnreadCount(): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount();
        
        return response()->json([
            'count' => $count,
            'display' => $count > 99 ? '99+' : (string)$count,
        ]);
    }

    /**
     * GET /api/notifications/recent
     * Return 10 most recent notifications with eager loading
     * 
     * @return JsonResponse
     */
    public function getRecent(): JsonResponse
    {
        $notifications = AdminNotification::with('activityLog.user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return $this->formatNotification($notification);
            })
            ->filter(); // Remove null values

        return response()->json([
            'notifications' => $notifications->values(), // Re-index array
        ]);
    }

    /**
     * GET /api/notifications
     * Return notifications with filters, sorting, and pagination
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AdminNotification::with('activityLog.user');

            // Filter by module
            if ($request->filled('module')) {
                $query->whereHas('activityLog', function ($q) use ($request) {
                    $q->where('module', $request->module);
                });
            }

            // Filter by activity_type
            if ($request->filled('activity_type')) {
                $query->whereHas('activityLog', function ($q) use ($request) {
                    $q->where('activity_type', $request->activity_type);
                });
            }

            // Filter by severity_level
            if ($request->filled('severity_level')) {
                $query->where('severity_level', $request->severity_level);
            }

            // Filter by read status
            if ($request->filled('read_status')) {
                if ($request->read_status === 'unread') {
                    $query->unread();
                } elseif ($request->read_status === 'read') {
                    $query->read();
                }
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $perPage = $request->get('per_page', 20);
            $notifications = $query->paginate($perPage);

            return response()->json([
                'notifications' => $notifications->map(function ($notification) {
                    return $this->formatNotification($notification);
                })->filter()->values(), // Remove null and re-index
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Failed to load notifications',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/notifications/{id}/mark-read
     * Mark single notification as read and clear cache
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->markAsRead();

        // Clear cache
        Cache::forget('admin_notifications_unread_count');

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu thông báo là đã đọc',
        ]);
    }

    /**
     * POST /api/notifications/mark-all-read
     * Mark all unread notifications as read and clear cache
     * 
     * @return JsonResponse
     */
    public function markAllAsRead(): JsonResponse
    {
        AdminNotification::unread()->update(['read_at' => now()]);

        // Clear cache
        Cache::forget('admin_notifications_unread_count');

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu tất cả thông báo là đã đọc',
        ]);
    }

    /**
     * GET /notifications
     * Return Blade view for full notification page
     * 
     * @return \Illuminate\View\View
     */
    public function page()
    {
        return view('notifications.index');
    }

    /**
     * Format notification for JSON response
     * 
     * @param AdminNotification $notification
     * @return array|null
     */
    private function formatNotification(AdminNotification $notification): ?array
    {
        $activityLog = $notification->activityLog;
        
        // Skip if activity log was deleted
        if (!$activityLog) {
            return null;
        }
        
        return [
            'id' => $notification->id,
            'severity_level' => $notification->severity_level,
            'is_unread' => $notification->isUnread(),
            'created_at' => $notification->created_at->toIso8601String(),
            'created_at_human' => $notification->created_at->diffForHumans(),
            'activity' => [
                'type' => $activityLog->activity_type,
                'type_label' => $activityLog->getActivityTypeLabel(),
                'module' => $activityLog->module,
                'module_label' => $activityLog->getModuleLabel(),
                'description' => $activityLog->description,
                'user_name' => $activityLog->user?->name ?? 'System',
            ],
            'link' => $this->notificationService->generateLink($activityLog),
        ];
    }
}
