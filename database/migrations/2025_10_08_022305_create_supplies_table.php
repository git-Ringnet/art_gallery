<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã vật tư');
            $table->string('name')->comment('Tên vật tư');
            $table->enum('type', ['frame', 'canvas', 'other'])->default('frame')->comment('Loại vật tư');
            $table->string('unit', 20)->comment('Đơn vị tính');
            $table->decimal('quantity', 10, 2)->default(0)->comment('Số lượng tồn kho');
            $table->decimal('min_quantity', 10, 2)->default(0)->comment('Số lượng tối thiểu');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->timestamps();
            
            $table->index('code');
            $table->index('type');
            $table->index('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
