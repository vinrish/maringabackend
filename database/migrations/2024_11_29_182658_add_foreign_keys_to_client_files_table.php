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
        Schema::table('client_files', function (Blueprint $table) {
            $table->foreign(['client_folder_id'])->references(['id'])->on('client_folders')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_files', function (Blueprint $table) {
            $table->dropForeign('client_files_client_folder_id_foreign');
        });
    }
};
