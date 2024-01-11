<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_masters', function (Blueprint $table) {
            $table->increments('leave_master_id')->index();
            $table->integer('leave_type_id')->nullable()->index();
            $table->string('finger_print_id')->index();
            $table->integer('num_of_day')->index();
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
        Schema::dropIfExists('leave_masters');
    }
}
