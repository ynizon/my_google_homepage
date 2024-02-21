<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarms', function (Blueprint $table) {
            $table->id();
            $table->boolean('status')->default(1);
            $table->boolean('day1')->default(0);
            $table->boolean('day2')->default(0);
            $table->boolean('day3')->default(0);
            $table->boolean('day4')->default(0);
            $table->boolean('day5')->default(0);
            $table->boolean('day6')->default(0);
            $table->boolean('day7')->default(0);
            $table->integer('hour')->default(0);
            $table->integer('minute')->default(0);
            $table->string('sound')->default("");
            $table->bigInteger('user_id')->default(0);

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarms');
    }
};
