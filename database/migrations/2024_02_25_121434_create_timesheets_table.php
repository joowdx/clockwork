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
            $table->char('digest', 128)->nullable();
            $table->string('span')->default('full');
            $table->ulid('timesheet_id')->nullable();
            $table->foreignUlid('employee_id')
                ->index()
                ->constrained()
                ->references('id')
                ->on('employees')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();
            $table->unique(['employee_id', 'month', 'span']);
            $table->unique(['timesheet_id', 'span']);
        });

        Schema::table('timesheets', function (Blueprint $table) {
            $table->foreign('timesheet_id')
                ->nullable()
                ->index()
                ->constrained()
                ->references('id')
                ->on('timesheets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->change();
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
