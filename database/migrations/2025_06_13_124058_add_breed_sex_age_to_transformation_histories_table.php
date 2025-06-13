<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBreedSexAgeToTransformationHistoriesTable extends Migration
{
    public function up()
    {
        Schema::table('transformation_histories', function (Blueprint $table) {
            $table->string('breed')->nullable();
            $table->string('sex')->nullable();
            $table->string('age')->nullable();
        });
    }

    public function down()
    {
        Schema::table('transformation_histories', function (Blueprint $table) {
            $table->dropColumn(['breed', 'sex', 'age']);
        });
    }
}
