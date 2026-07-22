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
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('id')->constrained('invoices')->cascadeOnDelete();
            $table->string('reference_number')->nullable()->after('payment_method');
            $table->decimal('balance_amount', 10, 2)->default(0.00)->after('amount');
            $table->string('status')->default('Fully Paid')->after('balance_amount'); // Fully Paid, Partial Payment, Pending
            $table->foreignId('service_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['invoice_id', 'reference_number', 'balance_amount', 'status']);
            $table->foreignId('service_id')->nullable(false)->change();
        });
    }
};
