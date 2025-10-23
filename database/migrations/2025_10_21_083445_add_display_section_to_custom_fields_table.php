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
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->string('display_section', 50)->default('custom')->after('field_options')->comment('Section hiển thị: header, customer_info, items, totals, notes, custom');
            $table->integer('section_order')->default(0)->after('display_order')->comment('Thứ tự trong section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropColumn(['display_section', 'section_order']);
        });
    }
};
