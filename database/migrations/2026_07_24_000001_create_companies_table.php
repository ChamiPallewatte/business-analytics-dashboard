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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry_type')->default('agency'); // agency, restaurant, retail, healthcare, real_estate, ecommerce, manufacturing, education, general
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->default('#2563eb');
            $table->string('secondary_color')->default('#0f172a');
            $table->string('subscription_plan')->default('pro'); // basic, pro, enterprise
            $table->string('subscription_status')->default('active'); // active, suspended, cancelled, trialing
            $table->integer('max_users')->default(10);
            $table->integer('storage_limit_mb')->default(1000);
            $table->string('timezone')->default('UTC');
            $table->string('currency')->default('USD');
            $table->json('custom_settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
