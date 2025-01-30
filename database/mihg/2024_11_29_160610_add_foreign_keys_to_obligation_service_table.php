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
        Schema::table('obligation_service', function (Blueprint $table) {
            $table->foreign(['obligation_id'])->references(['id'])->on('obligations')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['service_id'])->references(['id'])->on('services')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obligation_service', function (Blueprint $table) {
            $table->dropForeign('obligation_service_obligation_id_foreign');
            $table->dropForeign('obligation_service_service_id_foreign');
        });
    }
};
