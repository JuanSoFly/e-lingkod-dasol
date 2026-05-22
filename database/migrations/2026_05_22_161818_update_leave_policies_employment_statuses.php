<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\LeavePolicy;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the Regular Employee Vacation Leave and Regular Employee Sick Leave policies
        // to include more employment statuses in case they are set to something else in database.
        $policies = LeavePolicy::whereIn('name', [
            'Regular Employee Vacation Leave',
            'Regular Employee Sick Leave',
        ])->get();

        foreach ($policies as $policy) {
            $statuses = $policy->employment_statuses ?? [];
            if (!is_array($statuses)) {
                $statuses = [];
            }
            $required = ['regular', 'permanent', 'temporary', 'casual', 'probationary'];
            $policy->employment_statuses = array_values(array_unique(array_merge($statuses, $required)));
            $policy->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to just ['regular'] for these policies
        $policies = LeavePolicy::whereIn('name', [
            'Regular Employee Vacation Leave',
            'Regular Employee Sick Leave',
        ])->get();

        foreach ($policies as $policy) {
            $policy->employment_statuses = ['regular'];
            $policy->save();
        }
    }
};
