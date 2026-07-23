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
        $tables = [
            'clients',
            'services',
            'invoices',
            'expenses',
            'payments',
            'client_documents',
            'activity_logs',
            'settings',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'clients',
            'services',
            'invoices',
            'expenses',
            'payments',
            'client_documents',
            'activity_logs',
            'settings',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                });
            }
        }
    }
};
