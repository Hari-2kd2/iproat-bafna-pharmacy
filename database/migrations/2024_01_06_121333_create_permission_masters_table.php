<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permission_masters', function (Blueprint $table) {
            $table->id('permission_master_id');
            $table->time('min_duration')->index();
            $table->time('max_duration')->index();
            $table->time('total_duration')->index();
            $table->integer('monthly_limit')->index();
            $table->integer('created_by')->index()->nullable();  
            $table->integer('updated_by')->index()->nullable();  
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
        Schema::dropIfExists('permission_masters');
    }
}
