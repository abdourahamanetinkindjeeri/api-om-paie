<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'type' => $this->type,
            'montant' => abs($this->amount), // Valeur absolue du montant
            'direction' => $this->amount > 0 ? 'credit' : 'debit',
            'statut' => $this->status,
            'date_transaction' => $this->created_at?->toISOString(),
            'metadata' => $this->meta ?? [],
        ];
    }
}
