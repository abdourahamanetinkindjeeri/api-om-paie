<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\SenegalPhone;

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
        $nomMaxLength = config('ompaie.registration.nom_max_length', 255);
        $prenomMaxLength = config('ompaie.registration.prenom_max_length', 255);
        $adresseMaxLength = config('ompaie.registration.adresse_max_length', 500);
        $allowedIdTypes = implode(',', config('ompaie.registration.allowed_id_types', ['cin', 'passport']));
        $otpMinLength = 4;
        $otpMaxLength = 8;

        return [
            'telephone' => [
                'nullable',
                'string',
                new SenegalPhone(),
                'unique:users,telephone'
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'nom' => [
                'nullable',
                'string',
                "max:{$nomMaxLength}",
                'regex:/^[a-zA-ZÀ-ÿ\s\'-]+$/'
            ],
            'prenom' => [
                'nullable',
                'string',
                "max:{$prenomMaxLength}",
                'regex:/^[a-zA-ZÀ-ÿ\s\'-]+$/'
            ],
            'type_piece' => [
                'nullable',
                'string',
                "in:{$allowedIdTypes}"
            ],
            'numero' => [
                'nullable',
                'string',
                'unique:users,numero'
            ],
            'adresse' => [
                'nullable',
                'string',
                "max:{$adresseMaxLength}"
            ],
            'code' => [
                'nullable',
                'string',
                "min:{$otpMinLength}",
                "max:{$otpMaxLength}"
            ]
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
                $validator->errors()->add('identifier', 'Le téléphone ou l\'email est obligatoire.');
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        $nomMaxLength = config('ompaie.registration.nom_max_length', 255);
        $prenomMaxLength = config('ompaie.registration.prenom_max_length', 255);
        $adresseMaxLength = config('ompaie.registration.adresse_max_length', 500);
        $allowedIdTypes = config('ompaie.registration.allowed_id_types', ['cin', 'passport']);

        return [
            'telephone.unique' => 'Un compte existe déjà avec ce numéro de téléphone.',
            'email.email' => 'Le format de l\'adresse email est invalide.',
            'email.max' => 'L\'adresse email ne peut pas dépasser 255 caractères.',
            'email.unique' => 'Un compte existe déjà avec cette adresse email.',
            'nom.max' => "Le nom ne peut pas dépasser {$nomMaxLength} caractères.",
            'nom.regex' => 'Le nom ne peut contenir que des lettres, espaces, apostrophes et tirets.',
            'prenom.max' => "Le prénom ne peut pas dépasser {$prenomMaxLength} caractères.",
            'prenom.regex' => 'Le prénom ne peut contenir que des lettres, espaces, apostrophes et tirets.',
            'type_piece.in' => 'Type de pièce invalide (' . implode(', ', $allowedIdTypes) . ').',
            'numero.unique' => 'Un compte existe déjà avec ce numéro de pièce d\'identité.',
            'adresse.max' => "L'adresse ne peut pas dépasser {$adresseMaxLength} caractères.",
            'code.min' => 'Le code doit contenir au moins 4 caractères.',
            'code.max' => 'Le code doit contenir au maximum 8 caractères.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'telephone' => 'numéro de téléphone',
            'email' => 'adresse email',
            'nom' => 'nom',
            'prenom' => 'prénom',
            'type_piece' => 'type de pièce',
            'numero' => 'numéro de pièce',
            'adresse' => 'adresse',
            'code' => 'code'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
