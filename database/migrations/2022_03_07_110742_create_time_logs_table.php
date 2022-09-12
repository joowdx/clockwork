<?php

use App\Models\Enrollment;
use App\Models\Scanner;
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
        Schema::create('time_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('uid');
            $table->foreignIdFor(Scanner::class)->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignIdFor(Enrollment::class, 'enrollment_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('time');
            $table->string('state', 20);
            $table->timestamps();
            $table->unique(['enrollment_id', 'time', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_logs');
    }
};
