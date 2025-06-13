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
        Schema::create('transformation_histories', function (Blueprint $table) {
            $table->id();
            $table->string('user_session');
            $table->string('breed_detected');
            $table->string('replicate_prediction_id')->nullable();
            $table->string('result_image_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transformation_histories');
    }
};
