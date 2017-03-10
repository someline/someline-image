<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSomelineImageablesTable extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('someline_imageables', function(Blueprint $table) {
//            $table->increments('someline_imageable_id');
//            $table->unsignedInteger('user_id')->index();

            // Adding more table related fields here...
            $table->morphs('imageable', 'someline_imageable_index');
            $table->unsignedInteger('someline_image_id')->index();
            $table->boolean('is_main')->default(false);
            $table->string('type')->nullable();
            $table->json('data')->nullable();

//            $table->unsignedInteger('created_by')->nullable();
//            $table->timestamp('created_at')->nullable();
//            $table->ipAddress('created_ip')->nullable();
//            $table->unsignedInteger('updated_by')->nullable();
//            $table->timestamp('updated_at')->nullable();
//            $table->ipAddress('updated_ip')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('someline_imageables');
	}

}
