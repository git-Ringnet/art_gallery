<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_exports', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->comment('Năm export');
            $table->string('filename')->comment('Tên file');
            $table->string('file_path')->comment('Đường dẫn file');
            $table->bigInteger('file_size')->comment('Kích thước file (bytes)');
            $table->string('status')->default('completed')->comment('Trạng thái: processing, completed, failed');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->foreignId('exported_by')->constrained('users')->comment('Người export');
            $table->timestamp('exported_at')->comment('Ngày giờ export');
            $table->timestamps();
            
            $table->index(['year', 'exported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_exports');
    }
};
