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
        Schema::create('dogs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('age')->nullable();
            $table->enum('gender', ['macho', 'femea'])->nullable();
            $table->string('breed')->nullable();
            $table->string('owner_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('photo_url')->nullable();
            $table->enum('status', ['em_casa', 'perdido', 'em_busca_de_tutor'])->default('em_casa');
            $table->boolean('show_phone')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dogs');
    }
};