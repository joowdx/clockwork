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
        Schema::create('timelogs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->dateTime('time')->index();
            $table->unsignedSmallInteger('device')->index();
            $table->string('uid')->index();
            $table->unsignedTinyInteger('mode');
            $table->unsignedTinyInteger('state');
            $table->boolean('shadow')->default(false);
            $table->boolean('pseudo')->default(false);
            $table->foreign('device')->references('uid')->on('scanners')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unique(['device', 'uid', 'time', 'state', 'mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timelogs');
    }
};
