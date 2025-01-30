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
        Schema::create('businesses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id')->index('businesses_client_id_foreign');
            $table->string('name', 191);
            $table->string('business_email', 191)->nullable();
            $table->string('business_phone', 191)->nullable();
            $table->string('business_address', 191)->nullable();
            $table->string('business_no', 191);
            $table->date('registration_date');
            $table->timestamps(6);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
