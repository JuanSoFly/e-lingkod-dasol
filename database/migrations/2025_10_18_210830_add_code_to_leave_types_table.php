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
        // First add the column without unique constraint
        Schema::table('leave_types', function (Blueprint $table) {
            $table->string('code', 10)->after('name')->nullable(); // VL, SL, ML, PL, etc.
        });

        // Update existing leave types with appropriate codes
        DB::table('leave_types')->update(['code' => DB::raw('UPPER(LEFT(name, 2))')]);

        // Specific updates for proper codes (updated for new government standard)
        DB::table('leave_types')->where('name', 'Vacation Leave')->update(['code' => 'VL']);
        DB::table('leave_types')->where('name', 'Sick Leave')->update(['code' => 'SL']);
        DB::table('leave_types')->where('name', 'Maternity Leave')->update(['code' => 'ML']);
        DB::table('leave_types')->where('name', 'Paternity Leave')->update(['code' => 'PL']);
        DB::table('leave_types')->where('name', 'Solo Parent Leave')->update(['code' => 'SPL']);
        DB::table('leave_types')->where('name', 'Special Privilege Leave')->update(['code' => 'SPLV']);
        DB::table('leave_types')->where('name', 'Mandatory/Forced Leave')->update(['code' => 'MFL']);
        DB::table('leave_types')->where('name', '10-Day VAWC Leave')->update(['code' => 'VAWC']);
        DB::table('leave_types')->where('name', 'Compensatory Time Off')->update(['code' => 'CTO']);
        DB::table('leave_types')->where('name', 'Special Emergency (Calamity) Leave')->update(['code' => 'CALAM']);

        // Legacy leave types (for existing installations)
        DB::table('leave_types')->where('name', 'Special Leave Benefits for Women')->update(['code' => 'SLBW']);
        DB::table('leave_types')->where('name', 'Emergency Leave')->update(['code' => 'EL']);
        DB::table('leave_types')->where('name', 'Leave Without Pay')->update(['code' => 'LWOP']);

        // Make the column not nullable and add unique index
        Schema::table('leave_types', function (Blueprint $table) {
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
