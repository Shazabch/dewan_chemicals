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
        Schema::table('supplier_transactions', function (Blueprint $table) {
             $table->timestamp('reversed_at')->nullable()->after('source');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_transactions', function (Blueprint $table) {
             $table->dropColumn(['reversed_at', 'reversal_reason']);
        });
    }
};
