<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\PaymentController;


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
    Route::middleware('mongo.passport')->post('/logout', [AuthController::class, 'logout']);

    // Récupérer l'utilisateur connecté
    Route::middleware('mongo.passport')->get('/me', [AuthController::class, 'me']);
});

// Routes pour les transferts (protégées par authentification)
Route::middleware('mongo.passport')->prefix('transfer')->group(function () {
    // Effectuer un transfert
    Route::post('/', [TransferController::class, 'transfer']);

    // Vérifier si un numéro existe
    Route::post('/check-number', [TransferController::class, 'checkNumber']);

    // Obtenir le solde de l'utilisateur connecté
    Route::get('/balance', [TransferController::class, 'getBalance']);

    // Obtenir l'historique des transferts
    Route::get('/history', [TransferController::class, 'getTransferHistory']);
});

// Routes pour les paiements marchands (protégées par authentification)
Route::middleware('mongo.passport')->prefix('payment')->group(function () {
    // Effectuer un paiement vers un marchand
    Route::post('/', [PaymentController::class, 'payMerchant']);

    // Vérifier si un code marchand existe
    Route::post('/check-merchant', [PaymentController::class, 'checkMerchant']);

    // Obtenir les informations d'un marchand (publiques)
    Route::post('/merchant-info', [PaymentController::class, 'getMerchantInfo']);

    // Obtenir l'historique des paiements de l'utilisateur connecté
    Route::get('/history', [PaymentController::class, 'getUserPaymentHistory']);

    // Obtenir l'historique des paiements reçus (pour les marchands)
    Route::post('/merchant-history', [PaymentController::class, 'getMerchantPaymentHistory']);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
