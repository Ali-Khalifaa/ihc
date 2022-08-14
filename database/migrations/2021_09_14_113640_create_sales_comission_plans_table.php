<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesComissionPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_comission_plans', function (Blueprint $table) {
            $table->id();
            $table->double('individual_target_amount',20,2);
            $table->double('individual_percentage',8,2);
            $table->double('corporation_target_amount',20,2);
            $table->double('corporation_percentage',8,2);
            $table->bigInteger('comission_management_id')->unsigned();
            $table->foreign('comission_management_id')->references('id')->on('comission_management')->onDelete('cascade');

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
        Schema::dropIfExists('sales_comission_plans');
    }
}
