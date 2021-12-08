<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStreamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->string('id', 256)->primary();
            $table->string('name');
            $table->string('descriptions');
            $table->timestamps();
        });

        Schema::create('streams', function (Blueprint $table) {
            $table->string('id', 256)->primary();
            $table->string('channel_name');
            $table->string('stream_title');
            $table->string('game_name');
            $table->integer('viewer_count');
            $table->dateTime('started_at');
            $table->timestamps();
        });

        Schema::create('stream_tag', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('stream_id', 256);
            $table->string('tag_id', 256);

            $table->foreign('stream_id')->references('id')->on('streams');
            $table->foreign('tag_id')->references('id')->on('tags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('stream_tag');

        Schema::dropIfExists('streams');

        Schema::dropIfExists('tags');
    }
}
