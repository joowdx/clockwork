<?php

use App\Models\Scanner;
use App\Models\Timelog;
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
        Schema::create('timelogs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('uid');
            $table->foreignIdFor(Scanner::class)->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('time');
            $table->string('state', 20);
            $table->boolean('hidden')->default(false);
            $table->boolean('official')->default(true);
            $table->ulid('timelog_id');
            $table->timestamps();
            $table->unique(['uid', 'scanner_id', 'time', 'state']);
        });

        Schema::table('timelogs', function (Blueprint $table) {
            $table->ulid('timelog_id')->nullable()->references('id')->on('timelogs')->cascadeOnUpdate()->cascadeOnDelete()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timelogs');
    }
};
