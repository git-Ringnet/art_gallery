<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('year_databases', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique()->comment('Năm');
            $table->string('database_name')->comment('Tên database');
            $table->boolean('is_active')->default(false)->comment('Có phải năm hiện tại không');
            $table->boolean('is_on_server')->default(true)->comment('Database có trên server không');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->string('backup_location')->nullable()->comment('Vị trí backup (NAS/Cloud)');
            $table->timestamp('archived_at')->nullable()->comment('Ngày lưu trữ');
            $table->timestamps();
        });

        // Insert năm hiện tại
        DB::table('year_databases')->insert([
            'year' => 2025,
            'database_name' => env('DB_DATABASE', 'art_gallery'),
            'is_active' => true,
            'is_on_server' => true,
            'description' => 'Database năm hiện tại',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('year_databases');
    }
};
