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
        Schema::create('signatures', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->binary('specimen');
            $table->binary('certificate');
            $table->text('password');
            $table->ulidMorphs('signaturable');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['signaturable_type', 'signaturable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
