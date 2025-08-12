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
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('advance')->default(0.00)->after('opening_balance')->comment('Advance amount given by customer')->nullable();
            $table->decimal('credit_limit')->default(0.00)->after('advance')->comment('Credit limit for the customer')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['advance', 'credit_limit']);
        });
    }
};
