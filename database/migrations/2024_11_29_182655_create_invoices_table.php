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
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('client_id')->nullable()->index('invoices_client_id_foreign');
            $table->unsignedBigInteger('company_id')->nullable()->index('invoices_company_id_foreign');
            $table->unsignedBigInteger('business_id')->nullable()->index('invoices_business_id_foreign');
            $table->decimal('total_amount', 15);
            $table->decimal('amount_paid', 15)->default(0);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
