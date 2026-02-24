<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->date('import_date')->nullable()->after('image')->comment('Ngày nhập kho');
        });

        Schema::table('frames', function (Blueprint $table) {
            $table->date('import_date')->nullable()->after('status')->comment('Ngày nhập kho');
        });
    }

    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn('import_date');
        });

        Schema::table('frames', function (Blueprint $table) {
            $table->dropColumn('import_date');
        });
    }
};
