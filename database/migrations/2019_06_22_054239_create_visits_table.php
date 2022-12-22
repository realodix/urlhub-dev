<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('url_id');
            $table->unsignedBigInteger('user_id')->nullable($value = true);
            $table->string('visitor_id');
            $table->boolean('hits');
            $table->string('referer', 300)->nullable()->default(0);
            $table->ipAddress('ip');
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('device')->nullable();
            $table->string('os')->nullable();
            $table->string('os_version')->nullable();
            $table->timestamps();

            $table->foreign('url_id')
                ->references('id')->on('urls')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visits');
    }
};
