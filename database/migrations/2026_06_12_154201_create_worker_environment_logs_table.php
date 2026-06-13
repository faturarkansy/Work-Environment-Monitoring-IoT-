<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_environment_logs', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke user dengan role worker
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Metrik Hasil Kalkulasi Personal
            $table->float('pmv');
            $table->float('ppd');
            
            // Variabel Inputan Personal Worker
            $table->float('clothing_insulation'); // Nilai total Clo
            $table->string('activity_name');      // Label aktivitas (misal: Seated)
            $table->float('activity_met');       // Nilai Met (w_m2)
            
            // Snapshot Kondisi Lingkungan Saat Log Dibuat (Nilai Tunggal, Bukan String Koma)
            $table->float('temperature');
            $table->float('humidity');
            $table->float('air_speed');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_environment_logs');
    }
};