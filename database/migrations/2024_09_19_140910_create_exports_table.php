<?php

use App\Models\Period;
use App\Models\User;
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
        Schema::create('exports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('filename');
            $table->char('digest', 128)->nullable();
            $table->json('options');
            $table->text('exception')->nullable();
            $table->longText('content')->nullable();
            $table->string('status');
            $table->unsignedInteger('downloads')->default(0);
            $table->timestamp('downloaded_at')->nullable();
            $table->nullableUlidMorphs('exportable');
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
