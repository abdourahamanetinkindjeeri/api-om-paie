<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransferRequest extends FormRequest
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
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::where('telephone', $value)
                        ->orWhere('telephone', '+221' . $value)
                        ->orWhere('telephone', str_replace('+221', '', $value))
                        ->first();

                    if (!$user) {
                        $fail('Ce numéro de téléphone n\'existe pas dans notre système.');
                    }
                }
            ],
            'montant' => [
                'required',
                'numeric',
                'min:100',
                'max:2000000' // Limite maximale de transfert
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'numero.required' => 'Le numéro du destinataire est obligatoire.',
            'numero.string' => 'Le numéro doit être une chaîne de caractères.',
            'numero.exists' => 'Ce numéro n\'existe pas dans notre système.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 1.',
            'montant.max' => 'Le montant maximum autorisé est de 1,000,000.'
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
