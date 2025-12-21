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
        Schema::table('database_exports', function (Blueprint $table) {
            $table->boolean('includes_images')->default(false)->after('is_encrypted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('database_exports', function (Blueprint $table) {
            $table->dropColumn('includes_images');
        });
    }
};
