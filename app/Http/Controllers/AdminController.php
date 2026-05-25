<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EnvironmentLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Menampilkan halaman slideshow monitoring untuk semua pekerja.
     */
    public function allWorkers()
    {
        // 1. Ambil semua user dengan role 'worker'
        $workers = User::where('role', 'worker')->get();

        // 2. Ambil data sensor terbaru dari setiap unit_id sebagai data awal (initial state)
        // Kita mengambil log terbaru, lalu dikelompokkan berdasarkan unit_id
        $latestLogs = EnvironmentLog::whereIn('id', function($query) {
            $query->selectRaw('MAX(id)')
                ->from('environment_logs')
                ->groupBy('unit_id');
        })->orderBy('created_at', 'desc')->get();

        // 3. Kirim data ke frontend (Admin/AllWorkers.vue)
        return Inertia::render('Admin/AllWorkers', [
            'workers' => $workers,
            'latestLogs' => $latestLogs
        ]);
    }

    /**
     * (Opsional) Jika Anda ingin menambahkan fitur manajemen user nantinya.
     */
    public function index()
    {
        $users = User::all();
        return Inertia::render('Admin/UserManagement', [
            'users' => $users
        ]);
    }
}
