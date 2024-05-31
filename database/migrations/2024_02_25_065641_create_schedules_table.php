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
        Schema::create('schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->boolean('global')->default(false);
            $table->string('title')->nullable()->index();
            $table->date('start');
            $table->date('end');
            $table->enum('days', ['everyday', 'weekday', 'weekend', 'holiday'])->default('everyday');
            $table->string('arrangement')->default('');
            $table->jsonb('timetable')->nullable();
            $table->jsonb('threshold')->nullable();
            $table->foreignUlid('office_id')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUlid('requestor_id')->nullable()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamp('requested_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
