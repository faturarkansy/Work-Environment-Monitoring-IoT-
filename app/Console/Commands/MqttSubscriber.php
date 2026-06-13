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
    protected $description = 'Subscribe to MQTT broker and buffer environment data into MySQL database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mqtt = new \Bluerhinos\phpMQTT('broker.hivemq.com', 1883, 'LaravelClient_' . uniqid());

        if ($mqtt->connect()) {
            $this->info("Terhubung ke HiveMQ! Menunggu data dari ESP32...");

            // Format subscribe yang benar untuk library BlueRhinos:
            $topics['monitoring/lingkungan'] = [
                'qos' => 0,
                'function' => function ($topic, $msg) {
                    echo "\n[DEBUG] Ada pesan masuk di topik $topic: $msg\n";

                    $cleanMsg = preg_replace('/:\s*(-?\binf\b|-?\bnan\b)/i', ': 0.0', $msg);

                    // Sekarang decode menggunakan string yang sudah bersih dari kata nan kaku
                    $payload = json_decode($cleanMsg, true);
                    
                    if (!$payload) {
                        echo "[ERROR] Payload bukan JSON yang valid\n";
                        return;
                    }

                    $unitId = $payload['id'] ?? null;
                    if (!$unitId) return;

                    $cacheKey = "mqtt_buffer_unit_" . $unitId;

                    // Ambil atau inisialisasi buffer termasuk key 'wbgt'
                    $buffer = Cache::get($cacheKey, [
                        'airSpeed' => [],
                        'Twb' => [],
                        'Tdb' => [],
                        'MRT' => [],
                        'RH' => [],
                        'O2' => [],
                        'CO' => [],
                        'wbgt' => [] // Tambahan wadah buffer WBGT
                    ]);

                    // Ambil nilai sensor dengan fallback 0 jika kosong
                    $v   = parseFloatValue($payload['airSpeed'] ?? $payload['airspeed'] ?? 0);
                    $twb = parseFloatValue($payload['Twb'] ?? $payload['twb'] ?? 0);
                    $tdb = parseFloatValue($payload['Tdb'] ?? $payload['tdb'] ?? 0);
                    $mrt = parseFloatValue($payload['MRT'] ?? $payload['mrt'] ?? 0);
                    $rh  = parseFloatValue($payload['RH'] ?? $payload['rh'] ?? 0);
                    $o2  = parseFloatValue($payload['O2'] ?? $payload['o2'] ?? 0);
                    $co  = parseFloatValue($payload['CO'] ?? $payload['co'] ?? 0);

                    // --- PERBAIKAN: LOGIKA KALKULASI WBGT LOKAL PER DETIK (HAPUS SINTAKS 'let') ---
                    $tg = $mrt;
                    if ($v > 0.0) {
                        $tg = (0.4 * $mrt) + (0.6 * $tdb);
                    }
                    $calculatedWbgt = (0.7 * $twb) + (0.3 * $tg);

                    // Isi ke dalam array buffer masing-masing
                    $buffer['airSpeed'][] = $v;
                    $buffer['Twb'][]      = $twb;
                    $buffer['Tdb'][]      = $tdb;
                    $buffer['MRT'][]      = $mrt;
                    $buffer['RH'][]       = $rh;
                    $buffer['O2'][]       = $o2;
                    $buffer['CO'][]       = $co;
                    $buffer['wbgt'][]     = round($calculatedWbgt, 2); // Simpan presisi 2 desimal

                    if (count($buffer['airSpeed']) >= 10) {
                        // Simpan seluruh data deretan sebagai string terpisah koma ke DB
                        \App\Models\EnvironmentLog::create([
                            'unit_id'    => $unitId,
                            'air_speed'  => implode(', ', $buffer['airSpeed']),
                            'twb'        => implode(', ', $buffer['Twb']),
                            'tdb'        => implode(', ', $buffer['Tdb']),
                            'mrt'        => implode(', ', $buffer['MRT']),
                            'rh'         => implode(', ', $buffer['RH']),
                            'o2'         => implode(', ', $buffer['O2']),
                            'co'         => implode(', ', $buffer['CO']),
                            'wbgt'       => implode(', ', $buffer['wbgt']), // Simpan kolom baru wbgt ke DB
                        ]);

                        Cache::forget($cacheKey);
                        $this->info("\n[SUCCESS] Data 10 deret Unit $unitId + WBGT disimpan ke DB.");
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

// Helper function untuk mengamankan parsing numerik di sisi PHP backend
// Helper function yang diperketat untuk menangani inf, nan, dan teks rusak
function parseFloatValue($val) {
    // 1. Jika berupa string, bersihkan spasi dan ubah ke huruf kecil untuk validasi
    if (is_string($val)) {
        $trimmed = strtolower(trim($val));
        if ($trimmed === 'inf' || $trimmed === 'nan' || $trimmed === '-inf') {
            return 0.0;
        }
    }

    // 2. Cek apakah format dasarnya adalah numerik
    if (is_numeric($val)) {
        $floatVal = (float)$val;

        // 3. Proteksi lapis kedua jika lolos pembacaan float sistem
        if (is_nan($floatVal) || is_infinite($floatVal)) {
            return 0.0;
        }

        return $floatVal;
    }

    // 4. Fallback jika data rusak total / berupa string teks biasa
    return 0.0;
}