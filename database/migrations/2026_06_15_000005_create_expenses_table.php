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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('type'); // Hosting Purchase, Domain Purchase, Freelancer Cost, Paid Advertising, Software Subscription, Marketing, Other
            $table->string('vendor_name');
            $table->date('expense_date');
            $table->decimal('amount', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0.00);
            $table->string('payment_status')->default('Paid'); // Paid, Pending
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
