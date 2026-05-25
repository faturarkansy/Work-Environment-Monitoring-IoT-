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
            // Menggunakan text agar muat banyak karakter deretan angka
            $table->text('air_speed')->change();
            $table->text('twb')->change();
            $table->text('tdb')->change();
            $table->text('mrt')->change();
            $table->text('rh')->change();
            $table->text('o2')->change();
            $table->text('gas_volume')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('text', function (Blueprint $table) {
            //
        });
    }
};
