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
        Schema::table('client_folders', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_folder_id')->nullable()->after('id');
            $table->foreign('parent_folder_id')->references('id')->on('client_folders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_folders', function (Blueprint $table) {
            //
        });
    }
};
