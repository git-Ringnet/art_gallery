<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            // Quyền phạm vi dữ liệu
            $table->enum('data_scope', ['all', 'own', 'showroom', 'none'])->default('all')->comment('Phạm vi dữ liệu: all=tất cả, own=chỉ của mình, showroom=theo showroom, none=không xem');
            $table->json('allowed_showrooms')->nullable()->comment('Danh sách ID showroom được phép xem (null = tất cả)');
            $table->boolean('can_view_all_users_data')->default(true)->comment('Xem dữ liệu của tất cả nhân viên');
            $table->boolean('can_filter_by_showroom')->default(true)->comment('Được phép lọc theo showroom');
            $table->boolean('can_filter_by_user')->default(true)->comment('Được phép lọc theo nhân viên');
            $table->boolean('can_filter_by_date')->default(true)->comment('Được phép lọc theo ngày');
            $table->boolean('can_filter_by_status')->default(true)->comment('Được phép lọc theo trạng thái');
            $table->boolean('can_search')->default(true)->comment('Được phép tìm kiếm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropColumn([
                'data_scope',
                'allowed_showrooms',
                'can_view_all_users_data',
                'can_filter_by_showroom',
                'can_filter_by_user',
                'can_filter_by_date',
                'can_filter_by_status',
                'can_search',
            ]);
        });
    }
};
