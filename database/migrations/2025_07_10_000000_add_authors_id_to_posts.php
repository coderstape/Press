<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use coderstape\Press\Migration;

class AddAuthorsIdToPosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table($this->prefix . 'posts', function (Blueprint $table) {
            $table->unsignedInteger('author_id')->after('series_id')->nullable();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table($this->prefix . 'posts', function (Blueprint $table) {
            $table->dropColumn('author_id');
        });
    }
}