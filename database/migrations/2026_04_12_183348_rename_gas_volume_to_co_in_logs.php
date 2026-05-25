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
            // Cek jika kolom gas_volume masih ada, maka rename ke co
            if (Schema::hasColumn('environment_logs', 'gas_volume')) {
                $table->renameColumn('gas_volume', 'co');
            }
            // Jika kolom co belum ada sama sekali, buat baru
            elseif (!Schema::hasColumn('environment_logs', 'co')) {
                $table->text('co')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('environment_logs', function (Blueprint $table) {
            $table->renameColumn('co', 'gas_volume');
        });
    }
};
