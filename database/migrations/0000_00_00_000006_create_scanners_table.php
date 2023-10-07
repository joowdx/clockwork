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
            $table->ulid('id')->primary();
            $table->string('name', 20)->unique();
            $table->string('attlog_file', 120)->unique()->nullable();
            $table->string('print_text_colour', 20)->default('#000000');
            $table->string('print_background_colour', 20)->default('#ffffff');
            $table->string('remarks', 120)->nullable();
            $table->boolean('shared')->default(false);
            $table->boolean('priority')->default(false);
            $table->string('ip_address')->unique()->nullable();
            $table->integer('port')->nullable();
            $table->string('password')->nullable();
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
    }
};
