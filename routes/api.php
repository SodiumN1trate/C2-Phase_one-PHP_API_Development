<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ConcertController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});


Route::get('/concerts', [ConcertController::class, 'index']);
Route::get('/concerts/{id}', [ConcertController::class, 'show']);

Route::get('/concerts/{concert_id}/shows/{show_id}/seating', [ConcertController::class, 'seating']);

Route::post('/concerts/{concert_id}/shows/{show_id}/reservation', [ConcertController::class, 'reservation']);

Route::post('/concerts/{concert_id}/shows/{show_id}/booking', [ConcertController::class, 'booking']);

Route::post('/tickets', [ConcertController::class, 'getTickets']);
