<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('series_id')->default(0)->index();
            $table->string('identifier')->nullable()->index();
            $table->string('slug')->unique()->index();
            $table->string('title');
            $table->text('body');
            $table->text('meta');
            $table->enum('type', ['page', 'post', 'redirect'])->default('post');
            $table->enum('status', ['active', 'deleted'])->default('active')->index();
            $table->unsignedInteger('views_count')->default(0)->index();
            $table->datetime('published_at')->index()->nullable();
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
        Schema::dropIfExists('posts');
    }
}