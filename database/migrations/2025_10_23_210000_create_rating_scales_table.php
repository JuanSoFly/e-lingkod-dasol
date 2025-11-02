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
        Schema::create('rating_scales', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                // e.g., "Standard OPCR Scale", "Custom Department Scale"
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('scale_configuration');        // JSON with rating values and labels
            $table->json('qet_weights')->nullable();     // JSON with QET weighting
            $table->string('created_by', 50);
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_default']);
        });

        Schema::create('rating_scale_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_scale_id')->constrained()->onDelete('cascade');
            $table->integer('rating_value');              // 1-5 scale
            $table->string('rating_label', 50);           // e.g., "Outstanding", "Very Satisfactory"
            $table->decimal('min_percentage', 5, 2)->nullable();
            $table->decimal('max_percentage', 5, 2)->nullable();
            $table->string('color_code', 7)->nullable();  // Hex color for UI
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->unique(['rating_scale_id', 'rating_value']);
            $table->index(['rating_scale_id', 'display_order']);
        });

        Schema::create('office_rating_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->onDelete('cascade');
            $table->foreignId('rating_scale_id')->constrained()->onDelete('cascade');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 50);
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();

            $table->unique(['office_id', 'effective_from']);
            $table->index(['office_id', 'is_active']);
        });

        // Insert default rating scale
        $defaultScaleId = DB::table('rating_scales')->insertGetId([
            'name' => 'Standard OPCR Rating Scale',
            'description' => 'Default 5-point rating scale for OPCR system',
            'is_active' => true,
            'is_default' => true,
            'scale_configuration' => json_encode([
                'min_rating' => 1,
                'max_rating' => 5,
                'type' => 'standard_5_point'
            ]),
            'qet_weights' => json_encode([
                'quantity' => 0.4,
                'efficiency' => 0.3,
                'timeliness' => 0.3
            ]),
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert default rating scale values
        $defaultValues = [
            ['rating_value' => 5, 'rating_label' => 'Outstanding', 'min_percentage' => 95, 'max_percentage' => 100, 'color_code' => '#10b981', 'display_order' => 5],
            ['rating_value' => 4, 'rating_label' => 'Very Satisfactory', 'min_percentage' => 90, 'max_percentage' => 94.99, 'color_code' => '#3b82f6', 'display_order' => 4],
            ['rating_value' => 3, 'rating_label' => 'Satisfactory', 'min_percentage' => 80, 'max_percentage' => 89.99, 'color_code' => '#f59e0b', 'display_order' => 3],
            ['rating_value' => 2, 'rating_label' => 'Unsatisfactory', 'min_percentage' => 60, 'max_percentage' => 79.99, 'color_code' => '#f97316', 'display_order' => 2],
            ['rating_value' => 1, 'rating_label' => 'Poor', 'min_percentage' => 0, 'max_percentage' => 59.99, 'color_code' => '#ef4444', 'display_order' => 1],
        ];

        foreach ($defaultValues as $value) {
            DB::table('rating_scale_values')->insert([
                'rating_scale_id' => $defaultScaleId,
                'rating_value' => $value['rating_value'],
                'rating_label' => $value['rating_label'],
                'min_percentage' => $value['min_percentage'],
                'max_percentage' => $value['max_percentage'],
                'color_code' => $value['color_code'],
                'display_order' => $value['display_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_rating_scales');
        Schema::dropIfExists('rating_scale_values');
        Schema::dropIfExists('rating_scales');
    }
};