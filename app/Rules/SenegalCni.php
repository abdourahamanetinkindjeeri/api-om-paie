<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SenegalCni implements Rule
{
    public function passes($attribute, $value): bool
    {
        // CNI : 13 ou 14 caractères alphanumériques
        return preg_match('/^[A-Z0-9]{13,14}$/', $value);
    }

    public function message(): string
    {
        return 'Le :attribute doit être un numéro de CNI sénégalais valide (13 ou 14 caractères alphanumériques).';
    }
}
