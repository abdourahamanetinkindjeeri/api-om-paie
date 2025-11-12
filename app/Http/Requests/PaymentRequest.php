<?php

namespace App\Http\Requests;

use App\Rules\ActiveMerchant;
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
        $minAmount = config('ompaie.payment.amount.min', 100);
        $maxAmount = config('ompaie.payment.amount.max', 2000000);
        $maxDescriptionLength = config('ompaie.payment.description_max_length', 255);
        $maxReferenceLength = config('ompaie.payment.reference_externe_max_length', 100);
        $amountDecimals = config('ompaie.payment.amount_decimals', 2);

        return [
            'code_marchand' => [
                'required',
                'string',
                new ActiveMerchant()
            ],
            'montant' => [
                'required',
                'numeric',
                "min:{$minAmount}",
                "max:{$maxAmount}",
                "regex:/^\d+(\.\d{1,{$amountDecimals}})?$/" // Validation des décimales
            ],
            'description' => [
                'nullable',
                'string',
                "max:{$maxDescriptionLength}"
            ],
            'reference_externe' => [
                'nullable',
                'string',
                "max:{$maxReferenceLength}",
                'regex:/^[a-zA-Z0-9_-]+$/' // Format alphanumérique avec tirets et underscores
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $minAmount = config('ompaie.payment.amount.min', 100);
        $maxAmount = config('ompaie.payment.amount.max', 2000000);
        $maxDescriptionLength = config('ompaie.payment.description_max_length', 255);
        $maxReferenceLength = config('ompaie.payment.reference_externe_max_length', 100);
        $amountDecimals = config('ompaie.payment.amount_decimals', 2);

        return [
            'code_marchand.required' => 'Le code marchand est obligatoire.',
            'code_marchand.string' => 'Le code marchand doit être une chaîne de caractères.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => "Le montant minimum est de {$minAmount} FCFA.",
            'montant.max' => "Le montant maximum autorisé est de " . number_format($maxAmount, 0, ',', ' ') . " FCFA.",
            'montant.regex' => "Le montant ne peut avoir plus de {$amountDecimals} décimales.",
            'description.max' => "La description ne peut pas dépasser {$maxDescriptionLength} caractères.",
            'reference_externe.max' => "La référence externe ne peut pas dépasser {$maxReferenceLength} caractères.",
            'reference_externe.regex' => 'La référence externe ne peut contenir que des lettres, chiffres, tirets et underscores.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code_marchand' => 'code marchand',
            'montant' => 'montant',
            'description' => 'description',
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
