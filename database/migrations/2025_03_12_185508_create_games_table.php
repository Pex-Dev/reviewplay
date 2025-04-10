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
        Schema::create('games', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->timestamps();
            $table->string('name');
            $table->string('background_image');
            $table->text('description');
            $table->json('developers');
            $table->date('released');
            $table->json('genres');
            $table->json('tags');
            $table->json('platforms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
