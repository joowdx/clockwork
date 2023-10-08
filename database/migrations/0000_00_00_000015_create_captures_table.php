<?php

use App\Models\Scanner;
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
        Schema::create('captures', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('status')->default('capturing');
            $table->string('pid')->nullable();
            $table->string('command')->nullable();
            $table->unsignedBigInteger('runtime')->default(0);
            $table->text('result')->nullable();
            $table->uuid('uuid')->nullable()->index();
            $table->foreignIdFor(Scanner::class)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('captures');
    }
};
