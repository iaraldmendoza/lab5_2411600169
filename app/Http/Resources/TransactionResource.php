<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($r): array
    {
        return ['id' => $this->id,'type' => $this->type_label,'delta' => $this->delta,'product' => $this->product->name,'user' => $this->user?->name ?? 'Former employee','reference' => $this->reference,'balance_after' => $this->balance_after,'created_at' => $this->created_at->toIso8601String(),'url' => route('transactions.show', $this->id)];
    }
}
