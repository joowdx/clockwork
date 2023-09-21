<?php

use App\Models\Employee;
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
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->tinyInteger('type')->default(0);
            $table->jsonb('offices')->default('[]');
            $table->boolean('disabled')->default(false);
            $table->rememberToken();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->foreignIdFor(Employee::class)
                ->unique()
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
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
        Schema::dropIfExists('users');
    }
};
