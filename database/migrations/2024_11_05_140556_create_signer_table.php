<?php

use App\Models\Export;
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
        Schema::create('signer', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('meta')->nullable();
            $table->string('field')->nullable();
            $table->foreignIdFor(Export::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->ulidMorphs('signer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signer');
    }
};
