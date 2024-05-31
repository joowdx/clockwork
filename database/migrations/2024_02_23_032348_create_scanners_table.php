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
        Schema::create('scanners', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedSmallInteger('uid')->unique()->nullable();
            $table->string('name')->unique();
            $table->jsonb('print')->nullable();
            $table->string('remarks')->nullable();
            $table->boolean('priority')->default(false);
            $table->string('host')->unique()->nullable();
            $table->integer('port')->nullable();
            $table->string('pass')->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scanners');
    }
};
