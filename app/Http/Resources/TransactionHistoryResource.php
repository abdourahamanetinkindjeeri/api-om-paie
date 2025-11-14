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
        $data = $this->resource;

        // Support for both model instances and arrays
        if (is_array($data)) {
            return [
                'id' => $data['id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'type' => $data['type'] ?? null,
                'montant' => isset($data['amount']) ? abs($data['amount']) : null,
                'direction' => isset($data['amount']) ? ($data['amount'] > 0 ? 'credit' : 'debit') : null,
                'statut' => $data['status'] ?? null,
                'date_transaction' => isset($data['date']) ? $data['date'] : (isset($data['created_at']) ? $data['created_at'] : null),
                'metadata' => $data['meta'] ?? $data['metadata'] ?? [],
            ];
        }

        // Default behavior for model instances
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
