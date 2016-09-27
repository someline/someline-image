<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSomelineImagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('someline_images', function(Blueprint $table)
		{
			$table->increments('someline_image_id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('image_name');
            $table->text('exif')->nullable();
            $table->tinyInteger('is_gif')->default(0);
            $table->unsignedInteger('file_size');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
			$table->unsignedInteger('created_by')->nullable();
			$table->timestamp('created_at')->nullable();
			$table->ipAddress('created_ip')->nullable();
			$table->unsignedInteger('updated_by')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->ipAddress('updated_ip')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('someline_images');
	}

}
