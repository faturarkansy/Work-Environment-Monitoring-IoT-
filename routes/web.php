<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// --- ROUTE REDIRECT SETELAH LOGIN ---
Route::get('/dashboard', function () {
    $user = Auth::user();

    if ($user->role === 'admin') {
        // PERBAIKAN: Langsung render Dashboard.vue, jangan memanggil EnvironmentController@index
        return Inertia::render('Dashboard');
    } else {
        // Jika worker, lempar ke route my-status
        return redirect()->route('worker.display');
    }
})->middleware(['auth', 'verified'])->name('dashboard');

// --- GRUP MIDDLEWARE AUTH ---
Route::middleware('auth')->group(function () {

    // 1. ROUTE KHUSUS ADMIN
    // History tetap memanggil EnvironmentController@index karena memang itu tujuannya
    Route::get('/history', function (Request $request) {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('worker.display');
        }
        return app(EnvironmentController::class)->index($request);
    })->name('history.index');

    // All Workers memanggil AdminController@allWorkers (Slideshow PMV/PPD)
    Route::get('/all-workers', function () {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('worker.display');
        }
        return app(AdminController::class)->allWorkers();
    })->name('all-workers.index');

    // 2. ROUTE KHUSUS WORKER
    Route::get('/my-status', [WorkerController::class, 'index'])->name('worker.display');

    // 3. ROUTE PROFILE (Bisa diakses keduanya)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
