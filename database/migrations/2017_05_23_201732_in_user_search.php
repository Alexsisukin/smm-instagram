<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InUserSearch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('in_user_search', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('pk')->unique();
            $table->string('username');
            $table->string('full_name');
            $table->string('is_private');
            $table->string('profile_pic_url');
            $table->string('profile_pic_id');
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
       Schema::dropIfExists('in_user_search');
    }
}
