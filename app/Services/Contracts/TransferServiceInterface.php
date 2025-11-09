<?php

namespace App\Services\Contracts;

interface TransferServiceInterface extends BaseServiceInterface
{
    /**
     * Effectuer un transfert entre utilisateurs
     */
    public function transfer(string $senderNumber, string $receiverNumber, float $amount): array;

    /**
     * Vérifier si un numéro existe
     */
    public function checkUserExists(string $numero): bool;

    /**
     * Obtenir le solde d'un utilisateur
     */
    public function getUserBalance(string $numero): ?float;

    /**
     * Obtenir l'historique des transferts d'un utilisateur
     */
    public function getUserTransferHistory(string $numero, int $page = 1, int $limit = 10): array;
}
