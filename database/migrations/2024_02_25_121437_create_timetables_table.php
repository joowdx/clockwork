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
        Schema::create('timetables', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->date('date');
            $table->jsonb('punch');
            $table->string('holiday')->nullable();
            $table->unsignedSmallInteger('undertime')->default(0);
            $table->unsignedSmallInteger('overtime')->default(0);
            $table->unsignedSmallInteger('duration')->default(0);
            $table->boolean('half')->default(false);
            $table->boolean('absent')->default(false);
            $table->boolean('present')->default(false);
            $table->boolean('regular')->default(false);
            $table->boolean('invalid')->default(false);
            $table->boolean('rectified')->default(false);
            $table->foreignUlid('timesheet_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->unique(['date', 'timesheet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
