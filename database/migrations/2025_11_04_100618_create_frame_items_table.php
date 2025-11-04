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
        Schema::create('frame_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('frame_id')->constrained('frames')->onDelete('cascade')->comment('ID khung tranh');
            $table->foreignId('supply_id')->constrained('supplies')->onDelete('cascade')->comment('ID cây gỗ');
            $table->integer('tree_quantity')->default(1)->comment('Số lượng cây sử dụng');
            $table->decimal('length_per_tree', 10, 2)->comment('Chiều dài mỗi cây (cm)');
            $table->decimal('total_length', 10, 2)->comment('Tổng chiều dài = tree_quantity * length_per_tree');
            $table->boolean('use_whole_trees')->default(false)->comment('Có sử dụng nguyên cây không');
            $table->timestamps();
            
            $table->index('frame_id');
            $table->index('supply_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frame_items');
    }
};
