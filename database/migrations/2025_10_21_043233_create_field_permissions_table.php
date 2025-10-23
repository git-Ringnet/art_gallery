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
        Schema::create('field_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade')->comment('ID vai trò');
            $table->string('module', 50)->comment('Tên module');
            $table->string('field_name', 100)->comment('Tên trường');
            $table->boolean('is_hidden')->default(false)->comment('Ẩn trường');
            $table->boolean('is_readonly')->default(false)->comment('Chỉ đọc');
            $table->timestamps();
            
            $table->unique(['role_id', 'module', 'field_name']);
            $table->index(['role_id', 'module']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_permissions');
    }
};
