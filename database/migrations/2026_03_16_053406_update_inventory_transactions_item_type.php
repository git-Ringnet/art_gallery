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
        // Add processed_item to item_type ENUM
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN item_type ENUM('painting', 'supply', 'processed_item') COMMENT 'Loại sản phẩm'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN item_type ENUM('painting', 'supply') COMMENT 'Loại sản phẩm'");
    }
};
