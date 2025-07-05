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
        Schema::create('civil_service_eligibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            
            // Eligibility information
            $table->enum('eligibility_type', [
                'Professional',
                'Sub-professional', 
                'Second Level',
                'First Level',
                'RA 1080',
                'Bar/Board',
                'CES',
                'CESO',
                'Career Executive Service',
                'Fire Officer',
                'Penology Officer',
                'NAPOLCOM',
                'Other'
            ])->index();
            
            // Examination details
            $table->string('examination_name');
            $table->date('date_taken')->index();
            $table->decimal('rating', 5, 2)->nullable()->comment('Examination rating/score');
            $table->string('place_of_examination')->nullable();
            $table->string('certificate_number')->nullable()->unique();
            $table->string('license_number')->nullable();
            
            // Validity and status
            $table->date('valid_until')->nullable()->index();
            $table->enum('status', [
                'Active',
                'Expired', 
                'Suspended',
                'Revoked',
                'Pending Verification'
            ])->default('Active')->index();
            
            // Additional information
            $table->text('remarks')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->date('date_issued')->nullable();
            $table->boolean('is_lifetime_valid')->default(false);
            
            // Verification tracking
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Document attachments
            $table->string('certificate_file_path')->nullable();
            $table->string('verification_document_path')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['employee_id', 'eligibility_type']);
            $table->index(['status', 'valid_until']);
            $table->index('examination_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('civil_service_eligibilities');
    }
};