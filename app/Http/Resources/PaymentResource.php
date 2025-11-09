<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'payeur' => [
                'telephone' => $this->resource['user']['phone'],
                'nom_complet' => $this->resource['user']['name'],
                'nouveau_solde' => $this->resource['user']['new_balance']
            ],
            'marchand' => [
                'code' => $this->resource['merchant']['code'],
                'nom' => $this->resource['merchant']['name'],
                'nouveau_solde' => $this->resource['merchant']['new_balance']
            ],
            'date_paiement' => now()->toISOString(),
            'statut' => 'completed',
            'metadata' => $this->resource['metadata'] ?? null
        ];
    }
}
