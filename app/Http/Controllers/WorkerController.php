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
        event(new \App\Events\EnvironmentDataReceived($latestLog));

        return response()->json(['status' => 'success', 'message' => 'Log terekam!']);
    }
}