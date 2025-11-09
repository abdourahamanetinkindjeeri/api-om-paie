<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->resource['success'],
            'reference' => $this->resource['reference'],
            'montant' => $this->resource['amount'],
            'expediteur' => [
                'numero' => $this->resource['sender']['number'],
                'nom_complet' => $this->resource['sender']['name'],
                'nouveau_solde' => $this->resource['sender']['new_balance']
            ],
            'destinataire' => [
                'numero' => $this->resource['receiver']['number'],
                'nom_complet' => $this->resource['receiver']['name'],
                'nouveau_solde' => $this->resource['receiver']['new_balance']
            ],
            'date_transfert' => now()->toISOString(),
            'statut' => 'completed'
        ];
    }
}
