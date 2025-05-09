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
        Schema::create('manufacturing_expenses', function (Blueprint $table) {
            $table->id();
            $table->integer('manufacturing_flow_id')->nullable();
            $table->integer('expense_type_id')->nullable();
            $table->date('date_of_expense')->nullable();
            $table->double('amount')->default(0)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturing_expenses');
    }
};
