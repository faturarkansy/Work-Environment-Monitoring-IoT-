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
        Schema::create('environment_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('unit_id'); // Untuk membedakan Unit 1, 2, atau 3
            $table->float('air_speed')->default(0);
            $table->float('twb')->default(0); // Wet Temperature
            $table->float('tdb')->default(0); // Dry Temperature
            $table->float('mrt')->default(0); // Radiation Temperature
            $table->float('rh')->default(0);  // Humidity
            $table->float('o2')->default(0);  // Oxygen
            $table->float('gas_volume')->default(0);
            $table->timestamps(); // Akan otomatis membuat kolom created_at (waktu data masuk)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environment_logs');
    }
};
