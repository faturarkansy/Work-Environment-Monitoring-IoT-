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
        Schema::table('environment_logs', function (Blueprint $table) {
            $table->text('air_speed')->change();
            $table->text('twb')->change();
            $table->text('tdb')->change();
            $table->text('mrt')->change();
            $table->text('rh')->change();
            $table->text('o2')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('environment_logs', function (Blueprint $table) {
            //
        });
    }
};
