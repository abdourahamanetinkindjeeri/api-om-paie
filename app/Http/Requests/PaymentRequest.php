<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PaymentRequest extends FormRequest
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
            'code_marchand' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $merchant = \App\Models\Merchant::where('code', $value)
                        ->where('status', 'active')
                        ->first();

                    if (!$merchant) {
                        $fail('Ce code marchand n\'existe pas ou est inactif.');
                    }
                }
            ],
            'montant' => [
                'required',
                'numeric',
                'min:100',
                'max:2000000' // Limite maximale de paiement
            ],
            'description' => [
                'nullable',
                'string',
                'max:255'
            ],
            'reference_externe' => [
                'nullable',
                'string',
                'max:100'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code_marchand.required' => 'Le code marchand est obligatoire.',
            'code_marchand.string' => 'Le code marchand doit être une chaîne de caractères.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 100 FCFA.',
            'montant.max' => 'Le montant maximum autorisé est de 2,000,000 FCFA.',
            'description.max' => 'La description ne peut pas dépasser 255 caractères.',
            'reference_externe.max' => 'La référence externe ne peut pas dépasser 100 caractères.'
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
