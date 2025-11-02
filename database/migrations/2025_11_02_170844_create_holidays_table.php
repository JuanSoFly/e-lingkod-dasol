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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->date('observed_date')->nullable();
            $table->string('name');
            $table->enum('class', ['regular', 'special_non_working', 'special_working']);
            $table->enum('scope', ['national', 'regional', 'local'])->default('national');
            $table->string('scope_code')->nullable();
            $table->boolean('is_non_working')->default(true);
            $table->year('year')->index();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->index(['date', 'observed_date']);
            $table->index(['scope', 'scope_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
