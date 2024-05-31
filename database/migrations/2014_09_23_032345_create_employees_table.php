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
        Schema::create('employees', function (Blueprint $table) {
            $first_name = 'first_name';
            $middle_name = "CASE WHEN TRIM(middle_name) = '' THEN '' ELSE ', ' END, TRIM(middle_name)";
            $last_name = "last_name, ', '";
            $middle_initial = "CASE WHEN TRIM(middle_name) = '' THEN '' ELSE ' ' END, SUBSTRING(TRIM(middle_name), 1, 1), CASE WHEN LENGTH(TRIM(middle_name)) <= 1 THEN '' ELSE '.' END";
            $qualifier_name = "CASE WHEN qualifier_name = '' THEN '' ELSE ', ' END, qualifier_name";

            $table->ulid('id')->primary();
            $table->string('prefix_name', 60)->nullable();
            $table->string('suffix_name', 60)->nullable();
            $table->string('first_name', 60);
            $table->string('middle_name', 60)->default('');
            $table->string('last_name', 60);
            $table->string('qualifier_name', 60)->default('');
            $table->string('name', 240)->storedAs("TRIM(CONCAT($last_name, $first_name, $middle_initial, $qualifier_name))");
            $table->string('full_name', 240)->storedAs("TRIM(CONCAT($last_name, $first_name, $middle_name, $qualifier_name))");
            $table->date('birthdate')->nullable();
            $table->string('sex')->nullable();
            $table->string('designation')->nullable();
            $table->string('status')->default('');
            $table->string('substatus')->default('');
            $table->foreignUlid('office_id')->nullable();
            $table->char('uid', 8)->unique()->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['first_name', 'middle_name', 'last_name', 'qualifier_name'], 'unique_employee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
