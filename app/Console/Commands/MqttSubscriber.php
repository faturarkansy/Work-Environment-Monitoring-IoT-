<?php

namespace App\Console\Commands;

use App\Models\EnvironmentLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Command;

class MqttSubscriber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mqtt-subscriber';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Gunakan broker yang sesuai dengan ESP32 Anda
        $mqtt = new \Bluerhinos\phpMQTT('broker.hivemq.com', 1883, 'LaravelClient_' . uniqid());

        if ($mqtt->connect()) {
            $this->info("Terhubung ke HiveMQ! Menunggu data dari ESP32...");

            // Format subscribe yang benar untuk library BlueRhinos:
            $topics['monitoring/lingkungan'] = [
                'qos' => 0,
                'function' => function ($topic, $msg) { // Tambahkan kunci 'function' di sini
                    echo "\n[DEBUG] Ada pesan masuk di topik $topic: $msg\n";

                    $payload = json_decode($msg, true);
                    if (!$payload) {
                        echo "[ERROR] Payload bukan JSON yang valid\n";
                        return;
                    }

                    $unitId = $payload['id'] ?? null;
                    if (!$unitId) return;

                    $cacheKey = "mqtt_buffer_unit_" . $unitId;

                    // Ambil buffer
                    $buffer = Cache::get($cacheKey, [
                        'airSpeed' => [],
                        'Twb' => [],
                        'Tdb' => [],
                        'MRT' => [],
                        'RH' => [],
                        'O2' => [],
                        'CO' => []
                    ]);

                    // Isi buffer
                    $buffer['airSpeed'][] = $payload['airSpeed'] ?? 0;
                    $buffer['Twb'][]      = $payload['Twb'] ?? 0;
                    $buffer['Tdb'][]      = $payload['Tdb'] ?? 0;
                    $buffer['MRT'][]      = $payload['MRT'] ?? 0;
                    $buffer['RH'][]       = $payload['RH'] ?? 0;
                    $buffer['O2'][]       = $payload['O2'] ?? 0;
                    $buffer['CO'][]      = $payload['CO'] ?? 0;

                    if (count($buffer['airSpeed']) >= 10) {
                        // Simpan data sebagai deretan string, bukan rata-rata
                        \App\Models\EnvironmentLog::create([
                            'unit_id'    => $unitId,
                            'air_speed'  => implode(', ', $buffer['airSpeed']),
                            'twb'        => implode(', ', $buffer['Twb']),
                            'tdb'        => implode(', ', $buffer['Tdb']),
                            'mrt'        => implode(', ', $buffer['MRT']),
                            'rh'         => implode(', ', $buffer['RH']),
                            'o2'         => implode(', ', $buffer['O2']),
                            'co'         => implode(', ', $buffer['CO']),
                        ]);

                        // Reset cache
                        Cache::forget($cacheKey);
                        $this->info("\n[SUCCESS] Data 10 deret Unit $unitId disimpan ke DB.");
                    } else {
                        Cache::put($cacheKey, $buffer, 60);
                        echo "Buffering Unit $unitId: " . count($buffer['airSpeed']) . "/10\n";
                    }

                    event(new \App\Events\EnvironmentDataReceived($payload));
                }
            ];

            $mqtt->subscribe($topics);

            // Loop utama agar subscriber tetap aktif
            while ($mqtt->proc()) {
                // Menjaga koneksi tetap hidup
            }

            $mqtt->close();
        } else {
            $this->error("Gagal terhubung ke Broker.");
        }
    }
}
