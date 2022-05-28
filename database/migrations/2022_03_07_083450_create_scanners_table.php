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
            $table->uuid('id')->primary();
            $table->string('name', 20)->unique();
            $table->string('attlog', 120)->nullable();
            // $table->string('ip_address')->nullable();
            // $table->enum('protocol', ['tcp', 'udp'])->nullable();
            // $table->string('serial_number')->nullable();
            // $table->string('model')->nullable();
            // $table->string('version')->nullable();
            // $table->string('library')->nullable();
            $table->timestamps();
        });

        Schema::create('scanner_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->uuid('scanner_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'scanner_id']);
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
