<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('name');
            $table->string('office')->nullable();
            $table->boolean('regular');
            $table->boolean('active')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignUuid('last_updated_by')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_scanner', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('uid');
            $table->foreignUuid('scanner_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['uid', 'scanner_id']);
            $table->unique(['uid', 'employee_id']);
            $table->unique(['scanner_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');

        Schema::dropIfExists('employee_scanner');
    }
};
