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
        Schema::table('employees', function (Blueprint $table) {
            // PDS Panel 1: Personal Information - Additional fields
            $table->string('name_extension')->nullable()->after('last_name');
            $table->string('civil_status_other_details')->nullable()->after('civil_status');
            $table->string('dual_citizenship_type')->nullable()->after('citizenship');
            $table->string('dual_citizenship_country')->nullable()->after('dual_citizenship_type');
            
            // Residential Address (separate from general address)
            $table->string('res_house_block_lot_no')->nullable()->after('emergency_contact_address');
            $table->string('res_street')->nullable()->after('res_house_block_lot_no');
            $table->string('res_subdivision_village')->nullable()->after('res_street');
            $table->string('res_barangay')->nullable()->after('res_subdivision_village');
            $table->string('res_city_municipality')->nullable()->after('res_barangay');
            $table->string('res_province')->nullable()->after('res_city_municipality');
            $table->string('res_zip_code')->nullable()->after('res_province');
            
            // Permanent Address
            $table->string('perm_house_block_lot_no')->nullable()->after('res_zip_code');
            $table->string('perm_street')->nullable()->after('perm_house_block_lot_no');
            $table->string('perm_subdivision_village')->nullable()->after('perm_street');
            $table->string('perm_barangay')->nullable()->after('perm_subdivision_village');
            $table->string('perm_city_municipality')->nullable()->after('perm_barangay');
            $table->string('perm_province')->nullable()->after('perm_city_municipality');
            $table->string('perm_zip_code')->nullable()->after('perm_province');
            
            // Additional contact information
            $table->string('telephone_no')->nullable()->after('perm_zip_code');
            $table->string('mobile_no')->nullable()->after('telephone_no');
            
            // Agency Employee Number (separate from employee_number)
            $table->string('agency_employee_no')->nullable()->after('mobile_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'name_extension',
                'civil_status_other_details',
                'dual_citizenship_type',
                'dual_citizenship_country',
                'res_house_block_lot_no',
                'res_street',
                'res_subdivision_village',
                'res_barangay',
                'res_city_municipality',
                'res_province',
                'res_zip_code',
                'perm_house_block_lot_no',
                'perm_street',
                'perm_subdivision_village',
                'perm_barangay',
                'perm_city_municipality',
                'perm_province',
                'perm_zip_code',
                'telephone_no',
                'mobile_no',
                'agency_employee_no',
            ]);
        });
    }
};
