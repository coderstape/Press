<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use coderstape\Press\Migration;

class CreateSeriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->prefix . 'series', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->unique()->index();
            $table->string('title');
            $table->timestamps();

            $table->index('created_at');
            $table->index('updated_at');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->prefix . 'series');
    }
}