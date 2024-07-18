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
        Schema::create('timesheets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->date('month');
            $table->jsonb('details')->nullable();
            $table->foreignUlid('employee_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->char('digest', 128)->nullable();
            $table->timestamps();
            $table->unique(['month', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
