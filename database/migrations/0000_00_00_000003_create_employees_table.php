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
            $table->ulid('id')->primary();
            $table->jsonb('name')->unique();
            $table->string('office')->nullable();
            $table->boolean('regular');
            $table->boolean('active')->default(true);
            $table->boolean('csc_format')->default(true);
            $table->jsonb('groups')->nullable();
            $table->string('pin')->nullable();
            $table->timestamps();
            $table->rawIndex('groups jsonb_path_ops', 'employees_groups_idx')
                ->algorithm('gin');
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
    }
};
