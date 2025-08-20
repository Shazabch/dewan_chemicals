<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            // Link to the staff member
            $table->integer('staff_id')->nullable();
            $table->date('pay_period_start')->nullable();
            $table->date('pay_period_end')->nullable();
            // Salary components
            $table->decimal('base_salary', 10, 2)->comment('The base salary for this period')->nullable();
            $table->json('allowances')->nullable()->comment('JSON object for all additions, e.g., {"transport": 50, "bonus": 200}');
            $table->json('deductions')->nullable()->comment('JSON object for all subtractions, e.g., {"tax": 150, "insurance": 40}');

            // Calculated Totals
            $table->decimal('gross_salary', 10, 2)->nullable();
            $table->decimal('net_salary', 10, 2)->nullable();

            // Status & Payment Info
            $table->string('status')->default('generated');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // It's also a good idea to add a base salary column to your staff table
        // This will be the template for generating monthly salaries.
        Schema::table('admins', function (Blueprint $table) {
            $table->decimal('base_salary_amount', 10, 2)->default(0.00)->after('email'); // Or wherever you prefer
        });
    }

    public function down()
    {
        Schema::dropIfExists('salaries');
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('base_salary_amount');
        });
    }
};