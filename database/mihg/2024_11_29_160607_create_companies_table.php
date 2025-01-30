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
        Schema::create('companies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid');
            $table->string('name', 191);
            $table->string('logo', 191)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 191)->nullable();
            $table->string('address', 191)->nullable();
            $table->string('city', 191)->nullable();
            $table->string('country', 191)->nullable();
            $table->string('postal_code', 191)->nullable();
            $table->string('website', 191)->nullable();
            $table->string('kra_pin', 191)->nullable();
            $table->string('kra_obligations', 191)->nullable();
            $table->string('fiscal_year', 191)->nullable();
            $table->string('revenue', 191)->nullable();
            $table->string('employees', 191)->nullable();
            $table->string('industry', 191)->nullable();
            $table->string('county', 191)->nullable();
            $table->string('notes', 191)->nullable();
            $table->string('reg_date', 191)->nullable();
            $table->string('reg_number', 191)->nullable();
            $table->softDeletes();
            $table->timestamps(6);
            $table->unsignedBigInteger('client_id')->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
