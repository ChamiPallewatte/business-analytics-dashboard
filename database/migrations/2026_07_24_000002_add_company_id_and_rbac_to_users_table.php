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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->string('role')->default('employee')->change(); // super_admin, company_admin, employee
            $table->string('department')->nullable()->after('role');
            $table->string('position')->nullable()->after('department');
            $table->string('status')->default('active')->after('position'); // active, suspended, inactive
            $table->json('custom_permissions')->nullable()->after('status');
            $table->boolean('two_factor_enabled')->default(false)->after('custom_permissions');
            $table->timestamp('last_login_at')->nullable()->after('two_factor_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn([
                'company_id',
                'department',
                'position',
                'status',
                'custom_permissions',
                'two_factor_enabled',
                'last_login_at',
            ]);
        });
    }
};
