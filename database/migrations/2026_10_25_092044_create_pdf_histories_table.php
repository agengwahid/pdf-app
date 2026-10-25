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
        Schema::create('pdf_histories', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index();
            $table->string('action', 32)->index(); // merge, compress, split, manage
            $table->string('original_name', 255);
            $table->string('result_name', 255);
            $table->string('file_path', 500);
            $table->unsignedBigInteger('original_size')->default(0);
            $table->unsignedBigInteger('result_size')->default(0);
            $table->unsignedInteger('page_count')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_histories');
    }
};
