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
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_log_id')->comment('ID activity log tương ứng');
            $table->enum('severity_level', ['critical', 'warning', 'info'])->default('info')->comment('Mức độ quan trọng');
            $table->timestamp('read_at')->nullable()->comment('Thời điểm đọc thông báo');
            $table->timestamps();
            
            // Foreign key constraint with CASCADE delete
            $table->foreign('activity_log_id')
                  ->references('id')
                  ->on('activity_logs')
                  ->onDelete('cascade');
            
            // Indexes for common query patterns
            $table->index('read_at', 'idx_read_at');
            $table->index('created_at', 'idx_created_at');
            $table->index('severity_level', 'idx_severity_level');
            $table->index('activity_log_id', 'idx_activity_log_id');
            
            // Composite index for common query patterns (filtering unread + sorting by date)
            $table->index(['read_at', 'created_at'], 'idx_read_at_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
