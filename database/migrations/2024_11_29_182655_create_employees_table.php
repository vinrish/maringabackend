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
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->unique();
            $table->string('kra_pin', 191);
            $table->string('id_no', 191);
            $table->string('post_address', 191)->nullable();
            $table->string('post_code', 191)->nullable();
            $table->string('city', 191)->nullable();
            $table->string('county', 191)->nullable();
            $table->string('country', 191)->nullable();
            $table->string('position', 191)->nullable();
            $table->string('department', 191)->nullable();
            $table->decimal('salary')->default(0);
            $table->tinyInteger('employee_status')->default(0);
            $table->tinyInteger('employee_type')->default(0);
            $table->date('joining_date');
            $table->date('birth_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('user_id')->index('employees_user_id_foreign');
            $table->unsignedBigInteger('company_id')->index('employees_company_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
