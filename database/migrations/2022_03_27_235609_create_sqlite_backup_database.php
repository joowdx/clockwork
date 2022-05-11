<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlite';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->down();

        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->unique();
            $table->boolean('active')->default(false);
            $table->boolean('persist')->default(false);
        });

        Schema::create('time_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id');
            $table->foreignUuid('time_log_id');
            $table->dateTime('time');
            $table->string('state');
            $table->boolean('persist')->default(false);
        });

        Schema::create('test', function (Blueprint $table) {
            $table->id();
            $table->boolean('[*]');
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

        Schema::dropIfExists('time_logs');

        Schema::dropIfExists('test');
    }
};
