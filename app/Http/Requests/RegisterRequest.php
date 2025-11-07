<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SenegalPhone;
use App\Rules\SenegalCni;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'type_piece' => 'required|in:cin,passport',
            'numero' => ['required', 'unique:users', new SenegalCni],
            'adresse' => 'required|string|max:255',
            'telephone' => ['required', 'unique:users', new SenegalPhone],
            'email' => 'required|email|unique:users',
            'code' => ['required', 'digits:4'],
        ];
    }
}
