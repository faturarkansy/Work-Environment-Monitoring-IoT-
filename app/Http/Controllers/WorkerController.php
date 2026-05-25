<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentLog;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkerController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Mengambil data terbaru khusus untuk Unit ID sesuai ID Worker yang login
        $latestLog = EnvironmentLog::where('unit_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Mengambil riwayat 10 data terakhir untuk grafik mini di halaman worker
        $history = EnvironmentLog::where('unit_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return Inertia::render('Worker/Display', [
            'workerData' => $latestLog,
            'history' => $history,
            'auth' => [
                'user' => $user
            ]
        ]);
    }
}
