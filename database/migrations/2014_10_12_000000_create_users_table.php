<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('avatar');
            $table->string('confirmation_token');
            $table->smallInteger('is_active')->default(0);
            $table->string('questions_count')->default(0);
            $table->string('answers_count')->default(0);
            $table->string('comments_count')->default(0);
            $table->string('favorites_count')->default(0);
            $table->string('likes_count')->default(0);
            $table->string('followers_count')->default(0);
            $table->string('following_count')->default(0);
            $table->json('settings')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
