<?php

namespace App\Rules;

use App\Repositories\MerchantRepository;
use Illuminate\Contracts\Validation\Rule;

class ActiveMerchant implements Rule
{
    protected ?string $errorMessage = null;
    protected MerchantRepository $merchantRepository;

    public function __construct()
    {
        $this->merchantRepository = app(MerchantRepository::class);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // Validation du format du code marchand (lettres et chiffres, 6-20 caractères)
        if (!preg_match('/^[A-Z0-9]{6,20}$/', $value)) {
            $this->errorMessage = 'Le code marchand doit contenir entre 6 et 20 caractères alphanumériques majuscules.';
            return false;
        }

        // Vérification de l'existence et du statut du marchand
        $merchant = $this->merchantRepository->findByCode($value);

        if (!$merchant) {
            $this->errorMessage = 'Ce code marchand n\'existe pas.';
            return false;
        }

        if ($merchant->status !== 'active') {
            $this->errorMessage = 'Ce code marchand est actuellement inactif.';
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->errorMessage ?? 'Le code marchand est invalide.';
    }
}
