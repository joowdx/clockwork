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
        Schema::create('requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('status');
            $table->text('remarks')->nullable();
            $table->string('for')->default('approval');
            $table->ulidMorphs('requestable');
            $table->string('to')->nullable();
            $table->unsignedTinyInteger('step')->nullable();
            $table->boolean('completed')->default(false);
            $table->foreignUlid('user_id')->nullable()->constrained()->cascadeOnUpdate()->noActionOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
