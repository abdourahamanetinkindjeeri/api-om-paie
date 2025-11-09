<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SenegalPhone implements Rule
{
    public function passes($attribute, $value): bool
    {
        // Format international : +221 suivi d’un préfixe valide et 7 chiffres
        return preg_match('/^(?:\+221|221)?(77|76|78|75|70|71)[0-9]{7}$/', $value);
    }

    public function message(): string
    {
        return 'Le :attribute doit être un numéro de téléphone sénégalais valide (+221XXXXXXXXX).';
    }
}
