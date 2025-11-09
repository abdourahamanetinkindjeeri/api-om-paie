<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransferController;


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

    // Débloquer un compte (pour les tests/admin)
    Route::post('/unlock-account', [AuthController::class, 'unlockAccount']);

    // Déconnexion (protégée par Passport)
    Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);

    // Exemple : récupérer l'utilisateur connecté
    Route::middleware('auth:api')->get('/me', function () {
        return auth()->user();
    });
});

// Routes pour les transferts (protégées par authentification)
Route::middleware('auth:api')->prefix('transfer')->group(function () {
    // Effectuer un transfert
    Route::post('/', [TransferController::class, 'transfer']);

    // Vérifier si un numéro existe
    Route::post('/check-number', [TransferController::class, 'checkNumber']);

    // Obtenir le solde de l'utilisateur connecté
    Route::get('/balance', [TransferController::class, 'getBalance']);

    // Obtenir l'historique des transferts
    Route::get('/history', [TransferController::class, 'getTransferHistory']);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
