<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'telephone' => [
                'nullable',
                'string',
                'regex:/^\+221[0-9]{9}$/',
                'unique:users,telephone'
            ],
            'email' => [
                'nullable',
                'email',
                'unique:users,email'
            ],
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'type_piece' => 'nullable|string|in:cni,passeport,permis',
            'numero' => 'nullable|string|unique:users,numero',
            'adresse' => 'nullable|string|max:500',
            'code' => 'nullable|string|min:4|max:8'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Au moins un des deux (téléphone ou email) doit être fourni
            if (empty($this->telephone) && empty($this->email)) {
                $validator->errors()->add('identifier', 'Le téléphone ou l\'email est obligatoire');
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'telephone.regex' => 'Le format du numéro de téléphone est invalide (+221xxxxxxxxx)',
            'telephone.unique' => 'Un compte existe déjà avec ce numéro de téléphone',
            'email.email' => 'Format d\'email invalide',
            'email.unique' => 'Un compte existe déjà avec cet email',
            'type_piece.in' => 'Type de pièce invalide (cni, passeport, permis)',
            'numero.unique' => 'Un compte existe déjà avec ce numéro de pièce d\'identité',
            'code.min' => 'Le code doit contenir au moins 4 caractères',
            'code.max' => 'Le code doit contenir au maximum 8 caractères'
        ];
    }
}
