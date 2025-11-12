<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\SenegalPhone;

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
     * Normalise les champs pour compatibilité et impose téléphone + code_otp côté API
     * - Accepte code_otp et le mappe vers otp_code (utilisé par le contrôleur/service)
     * - Copie telephone vers identifier (clé attendue par le service)
     * - Si identifier est fourni sans telephone, on le recopie pour compat
     */
    protected function prepareForValidation()
    {
        $data = [];

        // Normaliser code_otp -> otp_code
        if ($this->has('code_otp') && !$this->has('otp_code')) {
            $data['otp_code'] = $this->input('code_otp');
        }

        // Normaliser telephone -> identifier
        if ($this->has('telephone') && !$this->has('identifier')) {
            $data['identifier'] = $this->input('telephone');
        }

        // Compat: si identifier est là sans telephone, recopie
        if ($this->has('identifier') && !$this->has('telephone')) {
            $data['telephone'] = $this->input('identifier');
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Ajoute les champs normalisés dans les données validées pour le contrôleur
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (!isset($data['identifier']) && $this->has('telephone')) {
            $data['identifier'] = $this->input('telephone');
        }
        if (!isset($data['otp_code']) && $this->has('code_otp')) {
            $data['otp_code'] = $this->input('code_otp');
        }

        return $data;
    }

    /**
     * Règles de validation pour la confirmation d'inscription par OTP.
     * Exigences: téléphone + otp_code
     * Les autres champs sont optionnels et permettent de compléter le profil lors de la confirmation.
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
                'required',
                'string',
                new SenegalPhone()
            ],
            'otp_code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/'
            ],
            // Données optionnelles pour compléter le profil
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
                'string'
            ],
            'adresse' => [
                'nullable',
                'string',
                "max:{$adresseMaxLength}"
            ],
            // Code PIN utilisateur optionnel à ce stade (peut être saisi lors de la confirmation)
            'code' => [
                'nullable',
                'string',
                "min:{$otpMinLength}",
                "max:{$otpMaxLength}"
            ]
        ];
    }

    /**
     * Messages d'erreurs personnalisés.
     */
    public function messages(): array
    {
        $nomMaxLength = config('ompaie.registration.nom_max_length', 255);
        $prenomMaxLength = config('ompaie.registration.prenom_max_length', 255);
        $adresseMaxLength = config('ompaie.registration.adresse_max_length', 500);
        $allowedIdTypes = config('ompaie.registration.allowed_id_types', ['cin', 'passport']);

        return [
            'telephone.required' => 'Le téléphone est obligatoire.',
            'telephone.string' => 'Le téléphone doit être une chaîne de caractères.',
            'otp_code.required' => 'Le code OTP est obligatoire.',
            'otp_code.size' => 'Le code OTP doit contenir exactement 6 chiffres.',
            'otp_code.regex' => 'Le code OTP doit contenir uniquement des chiffres.',

            'nom.max' => "Le nom ne peut pas dépasser {$nomMaxLength} caractères.",
            'nom.regex' => 'Le nom ne peut contenir que des lettres, espaces, apostrophes et tirets.',
            'prenom.max' => "Le prénom ne peut pas dépasser {$prenomMaxLength} caractères.",
            'prenom.regex' => 'Le prénom ne peut contenir que des lettres, espaces, apostrophes et tirets.',
            'type_piece.in' => 'Type de pièce invalide (' . implode(', ', $allowedIdTypes) . ').',
            'adresse.max' => "L'adresse ne peut pas dépasser {$adresseMaxLength} caractères.",
            'code.min' => 'Le code doit contenir au moins 4 caractères.',
            'code.max' => 'Le code doit contenir au maximum 8 caractères.'
        ];
    }

    /**
     * Libellés des attributs.
     */
    public function attributes(): array
    {
        return [
            'telephone' => 'téléphone',
            'otp_code' => 'code OTP',
            'nom' => 'nom',
            'prenom' => 'prénom',
            'type_piece' => 'type de pièce',
            'numero' => 'numéro de pièce',
            'adresse' => 'adresse',
            'code' => 'code'
        ];
    }

    /**
     * Gestion d'un échec de validation.
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
