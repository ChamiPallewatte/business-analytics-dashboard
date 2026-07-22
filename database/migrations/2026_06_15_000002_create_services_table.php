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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('type'); // Website Development, SEO, Google Ads, Social Media, Hosting, Domain, Maintenance, E-commerce, Other
            $table->date('start_date');
            $table->date('end_date');
            $table->date('renewal_date');
            $table->decimal('service_value', 10, 2);
            $table->decimal('vat_percent', 5, 2)->default(5.00);
            $table->decimal('vat_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('balance_amount', 10, 2)->default(0.00);
            $table->string('payment_status')->default('unpaid'); // unpaid, partially_paid, paid
            $table->string('vat_paid_status')->default('pending'); // pending, paid
            $table->string('service_status')->default('active'); // active, suspended, expired, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
