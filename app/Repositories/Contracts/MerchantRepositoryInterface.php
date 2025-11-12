<?php

namespace App\Repositories\Contracts;

use App\Models\Merchant;

interface MerchantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Trouver un marchand par son code.
     *
     * @param string $code
     * @return Merchant|null
     */
    public function findByCode(string $code): ?Merchant;

    /**
     * Trouver un marchand actif par son code avec cache.
     *
     * @param string $code
     * @return Merchant|null
     */
    public function findActiveByCode(string $code): ?Merchant;

    /**
     * Récupérer tous les marchands actifs.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllActive();
}
