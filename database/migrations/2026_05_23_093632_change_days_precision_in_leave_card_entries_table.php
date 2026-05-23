<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_card_entries', function (Blueprint $table) {
            $table->decimal('days', 5, 1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_card_entries', function (Blueprint $table) {
            $table->decimal('days', 3, 1)->change();
        });
    }
};
