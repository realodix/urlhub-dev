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
            $table->string('referer', 300)->nullable()->default(0);
            $table->ipAddress('ip');
            $table->string('browser');
            $table->string('browser_version');
            $table->string('device');
            $table->string('os');
            $table->string('os_version');
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
