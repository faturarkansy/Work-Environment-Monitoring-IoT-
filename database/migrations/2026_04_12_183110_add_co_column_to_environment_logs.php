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
            // Jika sebelumnya gas_volume sudah ada, kita rename saja:
            if (Schema::hasColumn('environment_logs', 'gas_volume')) {
                $table->renameColumn('gas_volume', 'co');
            } else {
                $table->text('co')->nullable();
            }
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
