<?php

namespace App\Http\Controllers;

// --- 1. IMPORT STATEMENT ---
use App\Models\WorkerEnvironmentLog; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 🔑 TAMBAHKAN INI agar beralih ke Facade murni
use Inertia\Inertia;

class WorkerController extends Controller
{
    /**
     * Tampilan utama halaman status worker
     */
    public function index()
    {
        return Inertia::render('Worker/Display');
    }

    /**
     * Menyimpan data PMV, PPD, dan kustomisasi aktivitas secara realtime
     */
    public function storeLog(Request $request)
    {
        $request->validate([
            'pmv' => 'required|numeric',
            'ppd' => 'required|numeric',
            'clothing_insulation' => 'required|numeric',
            'activity_name' => 'required|string',
            'activity_met' => 'required|numeric',
        ]);

        WorkerEnvironmentLog::create([
            'user_id' => Auth::id(), // 🔑 UBAH MENJADI INI (Sangat aman & dikenali semua IDE)
            'pmv' => $request->pmv,
            'ppd' => $request->ppd,
            'clothing_insulation' => $request->clothing_insulation,
            'activity_name' => $request->activity_name,
            'activity_met' => $request->activity_met,
        ]);

        $latestLog = WorkerEnvironmentLog::where('user_id', Auth::id())->latest()->first();

	if ($latestLog) {
            // Pemicu broadcast khusus untuk mengupdate data Clo & Met di halaman Admin secara aman
            event(new \App\Events\WorkerLogUpdated($latestLog->toArray()));
        }

        return response()->json(['status' => 'success', 'message' => 'Log terekam!']);
    }

    public function stopLog()
    {
        // Siarkan paket data bermuatan 0 / kosongan ke WebSocket
        event(new \App\Events\WorkerLogUpdated([
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'pmv' => 0.0,
            'ppd' => 0.0,
            'clothing_insulation' => 0.0,
            'activity_name' => 'Not Inputted Yet',
            'activity_met' => 0.0,
        ]));

        return response()->json(['status' => 'success', 'message' => 'Perekaman berhasil dihentikan.']);
    }

}
