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
        Schema::table('frames', function (Blueprint $table) {
            $table->decimal('frame_length', 10, 2)->nullable()->after('name')->comment('Chiều dài khung (cm)');
            $table->decimal('frame_width', 10, 2)->nullable()->after('frame_length')->comment('Chiều rộng khung (cm)');
            $table->decimal('perimeter', 10, 2)->nullable()->after('frame_width')->comment('Chu vi khung (cm)');
            $table->decimal('corner_deduction', 10, 2)->default(0)->after('perimeter')->comment('Khấu trừ góc xéo (cm)');
            $table->decimal('total_wood_needed', 10, 2)->nullable()->after('corner_deduction')->comment('Tổng chiều dài cây cần (cm)');
        });

        Schema::table('frame_items', function (Blueprint $table) {
            $table->decimal('wood_width', 10, 2)->nullable()->after('supply_id')->comment('Chiều rộng cây gỗ (cm)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('frames', function (Blueprint $table) {
            $table->dropColumn(['frame_length', 'frame_width', 'perimeter', 'corner_deduction', 'total_wood_needed']);
        });

        Schema::table('frame_items', function (Blueprint $table) {
            $table->dropColumn('wood_width');
        });
    }
};
