<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmRegistrationRequest extends FormRequest
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
            'identifier' => [
                'required',
                'string'
            ],
            'otp_code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/'
            ],
            // Données optionnelles pour compléter le profil
            'nom' => 'nullable|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'type_piece' => 'nullable|string|in:cni,passeport,permis',
            'numero' => 'nullable|string',
            'adresse' => 'nullable|string|max:500',
            'code' => 'nullable|string|min:4|max:8'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'L\'identifiant (téléphone ou email) est obligatoire',
            'otp_code.required' => 'Le code OTP est obligatoire',
            'otp_code.size' => 'Le code OTP doit contenir exactement 6 chiffres',
            'otp_code.regex' => 'Le code OTP doit contenir uniquement des chiffres',
            'type_piece.in' => 'Type de pièce invalide (cni, passeport, permis)',
            'code.min' => 'Le code doit contenir au moins 4 caractères',
            'code.max' => 'Le code doit contenir au maximum 8 caractères'
        ];
    }
}
