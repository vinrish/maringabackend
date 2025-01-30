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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id')->index('payrolls_employee_id_foreign');
            $table->enum('payroll_status', ['draft', 'final'])->default('draft');
            $table->decimal('gross_salary', 10);
            $table->decimal('taxable_income', 10);
            $table->decimal('net_salary', 10);
            $table->decimal('total_deductions', 10);
            $table->decimal('nssf_employee_contribution', 10);
            $table->decimal('nssf_employer_contribution', 10);
            $table->decimal('paye', 10)->default(0);
            $table->decimal('paye_net', 10);
            $table->decimal('paye_relief', 10);
            $table->decimal('nhif_employee_contribution', 10);
            $table->decimal('nhif_employer_contribution', 10);
            $table->decimal('nhif_relief', 10);
            $table->decimal('nita', 10);
            $table->decimal('housing_levy', 10);
            $table->decimal('net_housing_levy', 10, 0)->default(0);
            $table->decimal('housing_levy_relief', 10, 0)->default(0);
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->date('payroll_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
