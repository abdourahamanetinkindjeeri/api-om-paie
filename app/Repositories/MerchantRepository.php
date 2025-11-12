<?php

namespace App\Repositories;

use App\Models\Merchant;
use App\Repositories\Contracts\MerchantRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class MerchantRepository extends BaseRepository implements MerchantRepositoryInterface
{
    public function __construct(Merchant $model)
    {
        $this->model = $model;
    }

    /**
     * Trouver un marchand par son code.
     *
     * @param string $code
     * @return Merchant|null
     */
    public function findByCode(string $code): ?Merchant
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Trouver un marchand actif par son code avec cache.
     *
     * @param string $code
     * @return Merchant|null
     */
    public function findActiveByCode(string $code): ?Merchant
    {
        $cacheKey = "merchant:active:{$code}";
        $cacheTtl = config('ompaie.cache.merchant_ttl', 60);

        return Cache::remember($cacheKey, $cacheTtl * 60, function () use ($code) {
            return $this->model
                ->where('code', $code)
                ->where('status', 'active')
                ->first();
        });
    }

    /**
     * Récupérer tous les marchands actifs.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllActive()
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * Invalider le cache d'un marchand spécifique.
     *
     * @param string $code
     * @return void
     */
    public function clearCache(string $code): void
    {
        $cacheKey = "merchant:active:{$code}";
        Cache::forget($cacheKey);
    }

    /**
     * Surcharge de la méthode update pour invalider le cache.
     *
     * @param int|string $id
     * @param array $data
     * @return Merchant|null
     */
    public function update(int|string $id, array $data): ?Merchant
    {
        $merchant = parent::update($id, $data);

        if ($merchant) {
            $this->clearCache($merchant->code);
        }

        return $merchant;
    }
}
