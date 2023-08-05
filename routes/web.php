<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
Route::get('/dashboard', function () {
})->middleware(['auth', 'verified'])->name('dashboard');
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', 'Controller@dashboard')->name("dashboard");
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/', 'Controller@root');
Route::get('/main', 'Controller@main');
Route::get('/connect.php', 'Controller@connect');
Route::get('/privacy.php', 'Controller@privacy');
Route::get('/goGoogle', 'Controller@goGoogle');
Route::post('/changeAlbum', 'Controller@changeAlbum');
Route::get('/refresh', 'Controller@refresh');
Route::get('/refreshEmails', 'Controller@refreshEmails');
Route::post('/removeAll', 'Controller@removeAll');
Route::get('/removeAll', 'Controller@removeAll');
Route::get('/tos.php', 'Controller@tos');
Route::get('/picture', 'Controller@picture');
