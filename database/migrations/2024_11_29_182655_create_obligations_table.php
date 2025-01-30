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
        Schema::create('obligations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 191);
            $table->string('description', 191);
            $table->double('fee')->default(0);
            $table->double('amount')->default(0);
            $table->tinyInteger('type')->nullable()->default(0);
            $table->boolean('privacy')->default(false);
            $table->date('start_date');
            $table->tinyInteger('frequency')->nullable();
            $table->tinyInteger('is_recurring')->default(0);
            $table->date('next_run')->nullable();
            $table->date('last_run')->useCurrent();
            $table->tinyInteger('status')->default(1);
            $table->timestamps(6);
            $table->softDeletes();
            $table->unsignedBigInteger('client_id')->nullable()->index('obligations_client_id_foreign');
            $table->unsignedBigInteger('company_id')->nullable()->index('obligations_company_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obligations');
    }
};
