<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Fix decimal precision for financial fields in employees table
        Schema::table('employees', function (Blueprint $table) {
            // $table->decimal('basic_salary', 12, 2)->change();
        });

        // Fix decimal precision for leave credits if using float
        Schema::table('leave_credits', function (Blueprint $table) {
            //$table->decimal('used_days', 8, 2)->nullable()->change();
            //$table->decimal('remaining_days', 8, 2)->nullable()->change();
        });

        // Fix decimal precision for performance ratings
        Schema::table('performance_ratings', function (Blueprint $table) {
            $table->decimal('self_rating', 5, 2)->nullable()->change();
            $table->decimal('supervisor_rating', 5, 2)->nullable()->change();
            $table->decimal('final_rating', 5, 2)->nullable()->change();
            //$table->decimal('weight', 5, 2)->default(1.00)->change();
        });

        // Fix decimal precision for performance targets
        Schema::table('performance_targets', function (Blueprint $table) {
            $table->decimal('weight', 5, 2)->default(1.00)->change();
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->float('basic_salary')->change();
        });

        Schema::table('leave_credits', function (Blueprint $table) {
            $table->float('used_days')->nullable()->change();
            $table->float('remaining_days')->nullable()->change();
        });

        Schema::table('performance_ratings', function (Blueprint $table) {
            $table->float('self_rating')->nullable()->change();
            $table->float('supervisor_rating')->nullable()->change();
            $table->float('final_rating')->nullable()->change();
            $table->float('weight')->default(1.00)->change();
        });

        Schema::table('performance_targets', function (Blueprint $table) {
            $table->float('weight')->default(1.00)->change();
        });
    }
};