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
            $table->enum('edit_scope', ['all', 'own'])->default('all')->after('data_scope')->comment('Phạm vi sửa/xóa: all=theo phạm vi dữ liệu, own=chỉ của chính mình');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropColumn('edit_scope');
        });
    }
};
