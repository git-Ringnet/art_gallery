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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50)->comment('Tên module');
            $table->string('field_name', 100)->comment('Tên trường (key)');
            $table->string('field_label', 255)->comment('Nhãn hiển thị');
            $table->enum('field_type', ['text', 'number', 'date', 'textarea', 'select'])->default('text')->comment('Loại trường');
            $table->text('field_options')->nullable()->comment('Các tùy chọn (cho select)');
            $table->boolean('is_required')->default(false)->comment('Bắt buộc');
            $table->integer('display_order')->default(0)->comment('Thứ tự hiển thị');
            $table->timestamps();
            
            $table->unique(['module', 'field_name']);
            $table->index('module');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
