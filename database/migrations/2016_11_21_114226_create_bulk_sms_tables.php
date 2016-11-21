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
          $table->string('username')->nullable();
          $table->string('api_key', 100)->nullable();
          $table->unique('username', 'api_key')
          $table->softDeletes();
          $table->timestamps();
      });
      //  Bulk
      Schema::create('bulk', function(Blueprint $table)
      {
          $table->increment('id')->unsigned();
          $table->string('message', 160)->nullable();
          $table->integer('user_id')->unsigned();
          $table->softDeletes();
          $table->timestamps();
          $table->foreign('user_id')->references('id')->on('users');
      });
      //  SMS
      Schema::create('sms', function(Blueprint $table)
      {
          $table->increment('id')->unsigned();
          $table->string('number', 25);
          $table->integer('bulk_id')->unsigned();
          $table->decimal('cost', 5, 2)
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
      Schema::dropIfExists('sms');
      Schema::dropIfExists('bulk');
      Schema::dropIfExists('bulk_sms_settings');
    }
}
