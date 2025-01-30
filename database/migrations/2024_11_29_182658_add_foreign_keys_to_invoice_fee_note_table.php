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
        Schema::table('invoice_fee_note', function (Blueprint $table) {
            $table->foreign(['fee_note_id'])->references(['id'])->on('fee_notes')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['invoice_id'])->references(['id'])->on('invoices')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_fee_note', function (Blueprint $table) {
            $table->dropForeign('invoice_fee_note_fee_note_id_foreign');
            $table->dropForeign('invoice_fee_note_invoice_id_foreign');
        });
    }
};
