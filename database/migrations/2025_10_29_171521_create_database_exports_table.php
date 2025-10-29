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
        Schema::create('database_exports', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('filename');
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('exported_by')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->timestamps();

            $table->index('year');
            $table->index('status');
            $table->foreign('exported_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_exports');
    }
};
