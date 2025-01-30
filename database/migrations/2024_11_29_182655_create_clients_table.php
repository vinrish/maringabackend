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
        Schema::create('clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->unique();
            $table->string('kra_pin', 191);
            $table->string('id_no', 191);
            $table->string('post_address', 191)->nullable();
            $table->string('post_code', 191)->nullable();
            $table->string('city', 191);
            $table->string('county', 191);
            $table->string('country', 191)->nullable();
            $table->string('notes', 191)->nullable();
            $table->string('folder_path', 191)->nullable();
            $table->softDeletes();
            $table->timestamps(6);
            $table->unsignedBigInteger('user_id')->index('user_id');

            $table->index(['deleted_at', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
