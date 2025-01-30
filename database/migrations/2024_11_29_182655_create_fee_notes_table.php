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
        Schema::create('fee_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('task_id')->index('fee_notes_task_id_foreign');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->index('fee_notes_company_id_foreign');
            $table->unsignedBigInteger('business_id')->nullable()->index('fee_notes_business_id_foreign');
            $table->decimal('amount', 10);
            $table->boolean('status')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'company_id', 'deleted_at'], 'client_company_deleted_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_notes');
    }
};
