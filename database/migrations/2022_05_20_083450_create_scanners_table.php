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
        Schema::create('scanners', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('name', 20);
            $table->string('ip_address')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('model')->nullable();
            $table->string('library')->nullable();
            $table->timestamps();
        });

        Schema::create('scanner_user', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->uuid('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->uuid('scanner_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scanners');

        Schema::dropIfExists('scanner_user');
    }
};
