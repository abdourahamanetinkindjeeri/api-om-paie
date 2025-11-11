<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    private ?string $token;

    public function __construct($resource, string $token)
    {
        parent::__construct($resource);
        $this->token = $token;
    }

    public function toArray($request): array
    {
        return [
            'user' => [
                'id'        => $this->id,
                'nom'       => $this->nom,
                'prenom'    => $this->prenom,
                'type_piece' => $this->type_piece,
                'numero'    => $this->numero,
                'adresse'   => $this->adresse,
                'telephone' => $this->telephone,
                'email'     => $this->email,
                'created_at' => $this->created_at,
            ],
            'token' => $this->token,
            'token_type' => 'Bearer',
        ];
    }
}
