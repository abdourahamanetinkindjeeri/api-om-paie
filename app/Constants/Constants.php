<?php

namespace App\Constants;

/**
 * Constantes centralisées pour l'application OM-Paie
 */
class Constants
{
    // === STATUTS ===
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    // === TYPES DE TRANSACTION ===
    public const TRANSACTION_TYPE_PAYMENT = 'payment';
    public const TRANSACTION_TYPE_TRANSFER = 'transfer';
    public const TRANSACTION_TYPE_DEPOSIT = 'deposit';
    public const TRANSACTION_TYPE_WITHDRAWAL = 'withdrawal';

    // === DEVISES ===
    public const CURRENCY_XOF = 'XOF'; // Franc CFA

    // === OBJECTIFS OTP ===
    public const OTP_PURPOSE_REGISTRATION = 'registration';
    public const OTP_PURPOSE_PASSWORD_RESET = 'password_reset';
    public const OTP_PURPOSE_LOGIN = 'login';

    // === LIMITES OTP ===
    public const OTP_DEFAULT_EXPIRY_MINUTES = 5;
    public const OTP_DEFAULT_MAX_ATTEMPTS = 3;

    // === LIMITES UTILISATEUR ===
    public const USER_MAX_LOGIN_ATTEMPTS = 3;
    public const USER_BLOCK_DURATION_MINUTES = 30;

    // === LIMITES PAIEMENT ===
    public const PAYMENT_MIN_AMOUNT = 100; // 100 FCFA minimum
    public const PAYMENT_MAX_AMOUNT = 500000; // 500,000 FCFA maximum
    public const PAYMENT_MAX_DAILY_PAYMENTS = 10; // 10 paiements par jour
    public const PAYMENT_MAX_MONTHLY_AMOUNT = 2000000; // 2 millions FCFA par mois

    // === LIMITES TRANSFERT ===
    public const TRANSFER_MIN_AMOUNT = 500; // 500 FCFA minimum
    public const TRANSFER_MAX_AMOUNT = 1000000; // 1 million FCFA maximum
    public const TRANSFER_MAX_DAILY_TRANSFERS = 5; // 5 transferts par jour
    public const TRANSFER_MAX_MONTHLY_AMOUNT = 5000000; // 5 millions FCFA par mois

    // === ROUTES ===
    public const ROUTE_HOME = '/home';
}
