<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('Video')->default(0);
            $table->boolean('audio')->default(0);
            $table->boolean('photo')->default(0);
            $table->boolean('article')->default(0);
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
        Schema::dropIfExists('question_types');
    }
}
