<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\SenegalPhone;
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
        $minAmount = config('ompaie.transfer.amount.min', 100);
        $maxAmount = config('ompaie.transfer.amount.max', 2000000);
        $amountDecimals = config('ompaie.transfer.amount_decimals', 2);

        return [
            'telephone' => [
                'required',
                'string',
                new SenegalPhone(),
                'exists:users,telephone' // Vérifier que le destinataire existe
            ],
            'montant' => [
                'required',
                'numeric',
                "min:{$minAmount}",
                "max:{$maxAmount}",
                "regex:/^\d+(\.\d{1,{$amountDecimals}})?$/" // Validation des décimales
            ],
            'reference_externe' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_-]+$/' // Format alphanumérique avec tirets et underscores
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $minAmount = config('ompaie.transfer.amount.min', 100);
        $maxAmount = config('ompaie.transfer.amount.max', 2000000);
        $amountDecimals = config('ompaie.transfer.amount_decimals', 2);

        return [
            'telephone.required' => 'Le numéro du destinataire est obligatoire.',
            'telephone.string' => 'Le numéro doit être une chaîne de caractères.',
            'telephone.exists' => 'Ce numéro n\'existe pas dans notre système.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => "Le montant minimum est de {$minAmount} FCFA.",
            'montant.max' => "Le montant maximum autorisé est de " . number_format($maxAmount, 0, ',', ' ') . " FCFA.",
            'montant.regex' => "Le montant ne peut avoir plus de {$amountDecimals} décimales.",
            'reference_externe.max' => 'La référence externe ne peut pas dépasser 100 caractères.',
            'reference_externe.regex' => 'La référence externe ne peut contenir que des lettres, chiffres, tirets et underscores.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'telephone' => 'numéro de téléphone',
            'montant' => 'montant',
            'reference_externe' => 'référence externe'
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
