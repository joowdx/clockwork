<?php

use App\Models\Shift;
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
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->time('in1')->nullable();
            $table->time('in2')->nullable();
            $table->time('out1')->nullable();
            $table->time('out2')->nullable();
            $table->boolean('default')->default(false);
            $table->timestamps();
        });

        Shift::create([
            'id' => str()->orderedUuid(),
            'in1' => '08:00',
            'out1' => '11:00',
            'in2' => '13:00',
            'out2' => '16:00',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shifts');
    }
};
