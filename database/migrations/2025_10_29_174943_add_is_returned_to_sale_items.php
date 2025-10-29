<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('is_returned')->default(false)->after('total_vnd');
            $table->integer('returned_quantity')->default(0)->after('is_returned');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_returned', 'returned_quantity']);
        });
    }
};
