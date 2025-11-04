<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE debts MODIFY COLUMN status ENUM('unpaid', 'partial', 'paid', 'cancelled') DEFAULT 'unpaid' COMMENT 'Trạng thái'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update any 'cancelled' status to 'unpaid' before removing it from ENUM
        DB::table('debts')->where('status', 'cancelled')->update(['status' => 'unpaid']);
        
        DB::statement("ALTER TABLE debts MODIFY COLUMN status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid' COMMENT 'Trạng thái'");
    }
};
