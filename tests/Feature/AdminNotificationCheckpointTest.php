<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\ActivityLog;
use App\Models\AdminNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminNotificationCheckpointTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Role $adminRole;
    protected Role $regularRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->adminRole = Role::create(['name' => 'Admin', 'description' => 'Administrator']);
        $this->regularRole = Role::create(['name' => 'User', 'description' => 'Regular User']);

        // Create users
        $this->adminUser = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => $this->regularRole->id,
            'name' => 'Regular User',
            'email' => 'user@test.com',
        ]);
    }

    /** @test */
    public function test_database_migration_exists()
    {
        $this->assertTrue(
            \Schema::hasTable('admin_notifications'),
            'admin_notifications table should exist'
        );
    }

    /** @test */
    public function test_admin_notification_model_exists()
    {
        $this->assertTrue(
            class_exists('App\Models\AdminNotification'),
            'AdminNotification model should exist'
        );
    }

    /** @test */
    public function test_notification_service_exists()
    {
        $this->assertTrue(
            class_exists('App\Services\NotificationService'),
            'NotificationService should exist'
        );
    }

    /** @test */
    public function test_activity_log_observer_exists()
    {
        $this->assertTrue(
            class_exists('App\Observers\ActivityLogObserver'),
            'ActivityLogObserver should exist'
        );
    }

    /** @test */
    public function test_notification_controller_exists()
    {
        $this->assertTrue(
            class_exists('App\Http\Controllers\NotificationController'),
            'NotificationController should exist'
        );
    }

    /** @test */
    public function test_admin_middleware_exists()
    {
        $this->assertTrue(
            class_exists('App\Http\Middleware\AdminMiddleware'),
            'AdminMiddleware should exist'
        );
    }

    /** @test */
    public function test_notification_created_from_activity_log()
    {
        $activityLog = ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'activity_type' => 'create',
            'module' => 'sales',
            'description' => 'Created a new sale',
            'subject_type' => 'App\Models\Sale',
            'subject_id' => 1,
        ]);

        $this->assertDatabaseHas('admin_notifications', [
            'activity_log_id' => $activityLog->id,
        ]);
    }

    /** @test */
    public function test_notification_not_created_for_excluded_activity_types()
    {
        $excludedTypes = ['login', 'logout', 'view'];

        foreach ($excludedTypes as $type) {
            $activityLog = ActivityLog::create([
                'user_id' => $this->adminUser->id,
                'activity_type' => $type,
                'module' => 'auth',
                'description' => "User {$type}",
            ]);

            $this->assertDatabaseMissing('admin_notifications', [
                'activity_log_id' => $activityLog->id,
            ]);
        }
    }

    /** @test */
    public function test_severity_level_determination()
    {
        $service = app(NotificationService::class);

        // Critical
        $this->assertEquals('critical', $service->determineSeverityLevel('delete', 'sales'));
        $this->assertEquals('critical', $service->determineSeverityLevel('cancel', 'sales'));
        $this->assertEquals('critical', $service->determineSeverityLevel('bulk_delete', 'inventory'));

        // Warning
        $this->assertEquals('warning', $service->determineSeverityLevel('approve', 'sales'));
        $this->assertEquals('warning', $service->determineSeverityLevel('update', 'customers'));

        // Info
        $this->assertEquals('info', $service->determineSeverityLevel('create', 'sales'));
        $this->assertEquals('info', $service->determineSeverityLevel('import', 'inventory'));
    }

    /** @test */
    public function test_admin_notification_model_relationships()
    {
        $activityLog = ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'activity_type' => 'create',
            'module' => 'sales',
            'description' => 'Created a new sale',
        ]);

        $notification = AdminNotification::where('activity_log_id', $activityLog->id)->first();
        
        $this->assertNotNull($notification);
        $this->assertInstanceOf(ActivityLog::class, $notification->activityLog);
        $this->assertEquals($activityLog->id, $notification->activityLog->id);
    }

    /** @test */
    public function test_admin_notification_scopes()
    {
        $activityLog1 = ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'activity_type' => 'create',
            'module' => 'sales',
            'description' => 'Created sale 1',
        ]);

        $activityLog2 = ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'activity_type' => 'delete',
            'module' => 'sales',
            'description' => 'Deleted sale 2',
        ]);

        $notification1 = AdminNotification::where('activity_log_id', $activityLog1->id)->first();
        $notification2 = AdminNotification::where('activity_log_id', $activityLog2->id)->first();

        // Test unread scope
        $unreadCount = AdminNotification::unread()->count();
        $this->assertEquals(2, $unreadCount);

        // Mark one as read
        $notification1->markAsRead();

        // Test read/unread scopes
        $this->assertEquals(1, AdminNotification::unread()->count());
        $this->assertEquals(1, AdminNotification::read()->count());

        // Test severity scope
        $criticalCount = AdminNotification::bySeverity('critical')->count();
        $this->assertEquals(1, $criticalCount);
    }

    /** @test */
    public function test_mark_as_read_functionality()
    {
        $activityLog = ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'activity_type' => 'create',
            'module' => 'sales',
            'description' => 'Created a new sale',
        ]);

        $notification = AdminNotification::where('activity_log_id', $activityLog->id)->first();
        
        $this->assertTrue($notification->isUnread());
        $this->assertNull($notification->read_at);

        $notification->markAsRead();
        $notification->refresh();

        $this->assertFalse($notification->isUnread());
        $this->assertNotNull($notification->read_at);
    }
}
