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
            $table->boolean('can_view')->default(false)->comment('Quyền xem');
            $table->boolean('can_create')->default(false)->comment('Quyền thêm');
            $table->boolean('can_edit')->default(false)->comment('Quyền sửa');
            $table->boolean('can_delete')->default(false)->comment('Quyền xóa');
            $table->boolean('can_export')->default(false)->comment('Quyền xuất dữ liệu');
            $table->boolean('can_import')->default(false)->comment('Quyền nhập dữ liệu');
            $table->boolean('can_print')->default(false)->comment('Quyền in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropColumn([
                'can_view',
                'can_create',
                'can_edit',
                'can_delete',
                'can_export',
                'can_import',
                'can_print'
            ]);
        });
    }
};
