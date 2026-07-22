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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('business_type')->nullable()->after('company_name');
            $table->string('industry')->nullable()->after('business_type');
            $table->string('country')->default('United Arab Emirates')->after('address');
            $table->string('emirate')->nullable()->after('country');
            $table->string('city')->nullable()->after('emirate');
            $table->string('postal_code')->nullable()->after('city');
            $table->string('contact_designation')->nullable()->after('contact_person');
            $table->string('phone_code')->default('+971')->after('contact_designation');
            // Note: mobile is already present and will store the main phone number.
            $table->string('alternate_phone_code')->default('+971')->after('mobile');
            $table->string('alternate_phone_number')->nullable()->after('alternate_phone_code');
            $table->string('whatsapp_phone_code')->default('+971')->after('alternate_phone_number');
            $table->string('whatsapp_phone_number')->nullable()->after('whatsapp_phone_code');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('billing_cycle')->default('Monthly')->after('payment_status');
            $table->string('package_plan')->nullable()->after('billing_cycle');
            $table->text('description')->nullable()->after('package_plan');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('currency')->default('AED')->after('invoice_number');
            $table->json('items')->nullable()->after('remarks');
            $table->string('vat_period')->nullable()->after('items');
            $table->string('vat_due_month')->nullable()->after('vat_period');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->string('currency')->default('AED')->after('payment_method');
            $table->decimal('vat_percent', 5, 2)->default(5.00)->after('currency');
            $table->decimal('total_amount', 10, 2)->default(0.00)->after('vat_amount');
            $table->string('department')->nullable()->after('total_amount');
            $table->string('project')->nullable()->after('department');
            $table->string('cost_center')->nullable()->after('project');
            $table->string('tags')->nullable()->after('cost_center');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'business_type', 'industry', 'country', 'emirate', 'city', 'postal_code',
                'contact_designation', 'phone_code', 'alternate_phone_code', 'alternate_phone_number',
                'whatsapp_phone_code', 'whatsapp_phone_number'
            ]);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle', 'package_plan', 'description']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['currency', 'items', 'vat_period', 'vat_due_month']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method', 'currency', 'vat_percent', 'total_amount',
                'department', 'project', 'cost_center', 'tags'
            ]);
        });
    }
};
