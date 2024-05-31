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
        Schema::create('deployment', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->boolean('current')->default(false);
            $table->boolean('active')->default(true);
            $table->foreignUlid('office_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUlid('employee_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUlid('supervisor_id')->nullable()->constrained('employees')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->unique(['office_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployment');
    }
};
