<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\ActivityLog;
use App\Models\AdminNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminNotificationRoutesTest extends TestCase
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
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => $this->regularRole->id,
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'is_active' => true,
        ]);
    }

    public function test_unread_count_route_requires_authentication()
    {
        $response = $this->get('/api/notifications/unread-count');
        $response->assertRedirect('/login');
    }

    public function test_unread_count_route_requires_admin_role()
    {
        $response = $this->actingAs($this->regularUser)->get('/api/notifications/unread-count');
        $response->assertStatus(403);
    }

    public function test_unread_count_route_works_for_admin()
    {
        $response = $this->actingAs($this->adminUser)->get('/api/notifications/unread-count');
        $response->assertStatus(200);
        $response->assertJsonStructure(['count', 'display']);
    }

    public function test_recent_notifications_route_requires_admin()
    {
        $response = $this->actingAs($this->regularUser)->get('/api/notifications/recent');
        $response->assertStatus(403);
    }

    public function test_recent_notifications_route_works_for_admin()
    {
        $response = $this->actingAs($this->adminUser)->get('/api/notifications/recent');
        $response->assertStatus(200);
        $response->assertJsonStructure(['notifications']);
    }

    public function test_notifications_index_route_requires_admin()
    {
        $response = $this->actingAs($this->regularUser)->get('/api/notifications');
        $response->assertStatus(403);
    }

    public function test_notifications_index_route_works_for_admin()
    {
        $response = $this->actingAs($this->adminUser)->get('/api/notifications');
        $response->assertStatus(200);
        $response->assertJsonStructure(['notifications', 'pagination']);
    }

    public function test_mark_as_read_route_requires_admin()
    {
        // Create a notification
        $activityLog = ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'activity_type' => 'create',
            'module' => 'sales',
            'description' => 'Created a sale',
        ]);

        $notification = AdminNotification::where('activity_log_id', $activityLog->id)->first();

        $response = $this->actingAs($this->regularUser)
            ->post("/api/notifications/{$notification->id}/mark-read");
        
        $response->assertStatus(403);
    }

    public function test_mark_as_read_route_works_for_admin()
    {
        // Create a notification
        $activityLog = ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'activity_type' => 'create',
            'module' => 'sales',
            'description' => 'Created a sale',
        ]);

        $notification = AdminNotification::where('activity_log_id', $activityLog->id)->first();

        $response = $this->actingAs($this->adminUser)
            ->post("/api/notifications/{$notification->id}/mark-read");
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify notification is marked as read
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_all_as_read_route_requires_admin()
    {
        $response = $this->actingAs($this->regularUser)
            ->post('/api/notifications/mark-all-read');
        
        $response->assertStatus(403);
    }

    public function test_mark_all_as_read_route_works_for_admin()
    {
        // Create multiple notifications
        for ($i = 0; $i < 3; $i++) {
            ActivityLog::create([
                'user_id' => $this->adminUser->id,
                'activity_type' => 'create',
                'module' => 'sales',
                'description' => "Created sale {$i}",
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->post('/api/notifications/mark-all-read');
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all notifications are marked as read
        $unreadCount = AdminNotification::unread()->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function test_notifications_page_route_requires_admin()
    {
        $response = $this->actingAs($this->regularUser)->get('/notifications');
        $response->assertStatus(403);
    }

    public function test_notifications_page_route_works_for_admin()
    {
        $response = $this->actingAs($this->adminUser)->get('/notifications');
        $response->assertStatus(200);
    }
}
