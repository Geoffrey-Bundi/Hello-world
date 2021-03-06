<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBulkSmsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      //  settings
      Schema::create('bulk_sms_settings', function(Blueprint $table)
      {
          $table->string('code')->nullable();
          $table->string('username')->nullable();
          $table->string('api_key', 100)->nullable();
          $table->unique('code', 'username', 'api_key');
          $table->softDeletes();
          $table->timestamps();
      });
      //  Bulk
      Schema::create('bulk', function(Blueprint $table)
      {
          $table->increments('id')->unsigned();
          $table->string('message', 160)->nullable();
          $table->integer('round_id')->unsigned();
          $table->integer('user_id')->unsigned();
          $table->softDeletes();
          $table->timestamps();
          $table->foreign('round_id')->references('id')->on('rounds');
          $table->foreign('user_id')->references('id')->on('users');
      });
      //  SMS
      Schema::create('broadcast', function(Blueprint $table)
      {
          $table->increments('id')->unsigned();
          $table->string('number', 25);
          $table->integer('bulk_id')->unsigned();
          $table->integer('msg_id')->unsigned();
          $table->decimal('cost', 5, 2);
          $table->date('date_sent');
          $table->softDeletes();
          $table->timestamps();
          $table->foreign('bulk_id')->references('id')->on('bulk');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      //  Reverse migrations
      Schema::dropIfExists('broadcast');
      Schema::dropIfExists('bulk');
      Schema::dropIfExists('bulk_sms_settings');
    }
}
