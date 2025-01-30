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
        Schema::create('obligation_service', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('obligation_id');
            $table->unsignedBigInteger('service_id')->index('obligation_service_service_id_foreign');
            $table->double('price');
            $table->timestamps();

            $table->unique(['obligation_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obligation_service');
    }
};
