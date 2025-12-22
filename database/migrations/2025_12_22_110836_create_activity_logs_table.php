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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID người dùng thực hiện');
            $table->string('activity_type', 50)->comment('Loại hoạt động');
            $table->string('module', 50)->comment('Module/phân hệ');
            $table->text('description')->nullable()->comment('Mô tả hoạt động');
            $table->string('subject_type')->nullable()->comment('Loại đối tượng bị tác động');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('ID đối tượng bị tác động');
            $table->json('properties')->nullable()->comment('Dữ liệu bổ sung');
            $table->json('changes')->nullable()->comment('Thay đổi (old/new values)');
            $table->string('ip_address', 45)->nullable()->comment('Địa chỉ IP');
            $table->text('user_agent')->nullable()->comment('Thông tin trình duyệt');
            $table->boolean('is_suspicious')->default(false)->comment('Đánh dấu hoạt động đáng ngờ');
            $table->boolean('is_important')->default(false)->comment('Đánh dấu quan trọng (không xóa)');
            $table->timestamps();
            
            // Indexes
            $table->index('user_id', 'idx_user_id');
            $table->index('activity_type', 'idx_activity_type');
            $table->index('module', 'idx_module');
            $table->index(['subject_type', 'subject_id'], 'idx_subject');
            $table->index('created_at', 'idx_created_at');
            $table->index('ip_address', 'idx_ip_address');
            $table->index('is_suspicious', 'idx_suspicious');
            
            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
