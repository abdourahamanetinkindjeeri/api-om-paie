<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SenegalPhone;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // tu peux mettre une logique d'autorisation si besoin
    }

    public function rules(): array
    {
        return [
            // Téléphone Sénégal : format +221 suivi d’un préfixe valide et 7 chiffres
            'telephone' => [
                'required',
                'string',
                new SenegalPhone,
            ],

            'code' => [
                'required',
                'digits:4',
            ],
        ];
    }
}
