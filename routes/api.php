<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


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


Route::prefix('auth')->group(function () {
    // Inscription
    Route::post('/register', [AuthController::class, 'register']);

    // Connexion
    Route::post('/login', [AuthController::class, 'login']);

    // Déconnexion (protégée par Passport)
    Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);

    // Exemple : récupérer l’utilisateur connecté
    Route::middleware('auth:api')->get('/me', function () {
        return auth()->user();
    });
});



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
