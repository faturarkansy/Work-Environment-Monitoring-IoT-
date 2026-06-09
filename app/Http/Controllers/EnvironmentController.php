<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnvironmentController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('unit', 'all');
        $rawLogs = EnvironmentLog::orderBy('created_at', 'desc')->get();

        $expandedLogs = collect();

        foreach ($rawLogs as $item) {
            // Pecah string menjadi array
            $airSpeeds = explode(', ', $item->air_speed);
            $twbs      = explode(', ', $item->twb);
            $tdbs      = explode(', ', $item->tdb);
            $mrts      = explode(', ', $item->mrt);
            $rhs       = explode(', ', $item->rh);
            $o2s       = explode(', ', $item->o2);
            $cos       = explode(', ', $item->co);
            
            // PERBAIKAN: Pecah data string kolom wbgt dari database
            $wbgts     = $item->wbgt ? explode(', ', $item->wbgt) : [];

            // LOGIKA: Jabarkan data untuk filter 'all' DAN filter unit spesifik
            if ($filter === 'all' || $item->unit_id == $filter) {
                $tempBatch = [];

                for ($i = 0; $i < count($airSpeeds); $i++) {
                    $tempBatch[] = [
                        'id' => $item->id . '_' . $i,
                        'formatted_date' => $item->created_at->copy()->subSeconds(9 - $i)->format('Y-m-d H:i:s'),
                        'unit_id' => $item->unit_id,
                        'air_speed' => $airSpeeds[$i] ?? 0,
                        'twb' => $twbs[$i] ?? 0,
                        'tdb' => $tdbs[$i] ?? 0,
                        'mrt' => $mrts[$i] ?? 0,
                        'rh' => $rhs[$i] ?? 0,
                        'o2' => $o2s[$i] ?? 0,
                        'co' => $cos[$i] ?? 0,
                        
                        // PERBAIKAN: Petakan nilai WBGT sesuai urutan detiknya ($i)
                        'wbgt' => $wbgts[$i] ?? '0.00',
                    ];
                }

                // Masukkan batch yang sudah dibalik (descending per detik)
                foreach (array_reverse($tempBatch) as $log) {
                    $expandedLogs->push($log);
                }
            }
        }

        return Inertia::render('History', [
            'logs' => $expandedLogs,
            'currentFilter' => $filter
        ]);
    }
}