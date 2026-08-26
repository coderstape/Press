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
        // Press auto-loads its migrations via loadMigrationsFrom(), so this
        // runs against EVERY consumer's database on their next deploy --
        // including Sportsman, where `a_i_contents` was created years ago by
        // a host migration back when AIContent lived in App\Models. An
        // unguarded Schema::create() fails that deploy instead of no-opping.
        if (Schema::hasTable('a_i_contents')) {
            return;
        }

        Schema::create('a_i_contents', function (Blueprint $table) {
            $table->id();
            $table->json('data');
            $table->morphs('contentable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_i_contents');
    }
};
