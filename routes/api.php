<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComptesController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QrCodeController;


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

// Status endpoint simple
Route::get('/status', function () {
    return response()->json(['status' => 'running']);
});

Route::middleware('throttle:10,1')->prefix('auth')->group(function () {
    // Inscription - OTP flow: init via /register, confirm via /confirmation, resend via /resend
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register/confirmation', [AuthController::class, 'confirm']);
    Route::post('/resend', [AuthController::class, 'resendOtp']);

    // Connexion (2FA: étape 1 - vérifier PIN et envoyer OTP)
    Route::post('/login', [AuthController::class, 'login']);

    // Confirmer la connexion (2FA: étape 2 - vérifier OTP)
    Route::post('/login/confirm', [AuthController::class, 'confirmLogin']);

    // Rafraîchir le token (public)
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Débloquer un compte (pour les tests/admin)
    Route::post('/unlock-account', [AuthController::class, 'unlockAccount']);

    // Déconnexion (protégée par Passport)
    Route::middleware('mongo.passport')->post('/logout', [AuthController::class, 'logout']);

    // Récupérer l'utilisateur connecté (avec tous les comptes et historique du principal)
    Route::middleware('mongo.passport')->get('/me', [AuthController::class, 'me']);
});

// Routes pour les transferts (protégées par authentification)
Route::middleware(['mongo.passport', 'throttle:60,1'])->prefix('transfer')->group(function () {
    // Effectuer un transfert
    Route::post('/', [TransferController::class, 'transfer']);

    // Vérifier si un numéro existe
    Route::post('/check-number', [TransferController::class, 'checkNumber']);
});

// Routes pour les comptes (protégées par authentification)
Route::middleware(['mongo.passport', 'throttle:60,1'])->prefix('comptes')->group(function () {
    // Lister tous les comptes de l'utilisateur
    Route::get('/', [ComptesController::class, 'getUserComptes']);

    // Obtenir les informations d'un compte spécifique
    Route::get('/{numeroCompte}', [ComptesController::class, 'getBalance']);

    // Obtenir le solde d'un compte spécifique
    Route::get('/{numeroCompte}/balance', [ComptesController::class, 'getAccountBalance']);

    // Obtenir l'historique d'un compte spécifique
    Route::get('/{numeroCompte}/history', [ComptesController::class, 'getAccountHistory']);

    // Effectuer un transfert depuis un compte spécifique
    Route::post('/{numeroCompte}/transfer', [ComptesController::class, 'transferFromAccount']);

    // Effectuer un paiement depuis un compte spécifique
    Route::post('/{numeroCompte}/payment', [ComptesController::class, 'payFromAccount']);
});

// Routes pour les paiements marchands (protégées par authentification)
Route::middleware(['mongo.passport', 'throttle:60,1'])->prefix('payment')->group(function () {
    // Effectuer un paiement vers un marchand
    Route::post('/', [PaymentController::class, 'payMerchant']);

    // Vérifier si un code marchand existe
    Route::post('/check-merchant', [PaymentController::class, 'checkMerchant']);

    // Obtenir les informations d'un marchand (publiques)
    Route::post('/merchant-info', [PaymentController::class, 'getMerchantInfo']);

    // Obtenir l'historique des paiements reçus (pour les marchands)
    Route::post('/merchant-history', [PaymentController::class, 'getMerchantPaymentHistory']);
});

// Routes pour l'historique unifié (protégées par authentification)
Route::middleware(['mongo.passport', 'throttle:60,1'])->prefix('history')->group(function () {
    // Historique complet (paiements + transferts) trié par date décroissante
    Route::get('/', [HistoryController::class, 'getUserHistory']);

    // Historique des transferts seulement
    Route::get('/transfers', [HistoryController::class, 'getUserTransferHistory']);

    // Historique des paiements seulement
    Route::get('/payments', [HistoryController::class, 'getUserPaymentHistory']);

    // Statistiques utilisateur
    Route::get('/stats', [HistoryController::class, 'getUserStats']);
});

// Routes pour les QR codes (protégées par authentification)
Route::middleware(['mongo.passport', 'throttle:60,1'])->prefix('user')->group(function () {
    // Générer le QR code de l'utilisateur connecté
    Route::get('/qrcode', [QrCodeController::class, 'generateUserQrCode']);

    // Obtenir les données du QR code de l'utilisateur connecté
    Route::get('/qrcode/data', [QrCodeController::class, 'getUserQrCodeData']);
});

// Route pour scanner un QR code (protégée par authentification)
Route::middleware(['mongo.passport', 'throttle:60,1'])->post('/qrcode/scan', [QrCodeController::class, 'scanQrCode']);

// Route pour le health check (Docker)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'OM-Paie API',
        'version' => '2.0.0'
    ]);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
