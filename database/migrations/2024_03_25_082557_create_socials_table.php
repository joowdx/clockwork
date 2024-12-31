<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('socials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('provider_id');
            $table->string('provider');
            $table->ulidMorphs('sociable');
            $table->jsonb('data')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_id', 'sociable_type', 'sociable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('socials');
    }
};
