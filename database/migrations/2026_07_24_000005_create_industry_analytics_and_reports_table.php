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
        Schema::create('industry_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('industry_type'); // agency, restaurant, retail, healthcare, real_estate, ecommerce, manufacturing, education, general
            $table->string('metric_name');
            $table->string('metric_category');
            $table->decimal('metric_value', 15, 2)->default(0.00);
            $table->date('metric_date');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('type'); // financial, campaign, inventory, reservation, general
            $table->string('industry_type')->default('general');
            $table->json('data')->nullable();
            $table->string('format')->default('pdf'); // pdf, excel, csv
            $table->string('status')->default('completed'); // pending, processing, completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('industry_analytics');
    }
};
