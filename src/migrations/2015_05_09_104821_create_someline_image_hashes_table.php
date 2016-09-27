<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSomelineImageHashesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('someline_image_hashes', function(Blueprint $table)
		{
            $table->increments('someline_image_hash_id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->unsignedInteger('someline_image_id')->index();
            $table->char('file_sha1', 40);
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
		Schema::drop('someline_image_hashes');
	}

}
