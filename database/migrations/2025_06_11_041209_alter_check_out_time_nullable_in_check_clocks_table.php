<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('check_clocks', function (Blueprint $table) {
            $table->time('check_out_time')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('check_clocks', function (Blueprint $table) {
            $table->time('check_out_time')->nullable(false)->change();
        });
    }

};
