<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canReceiveOrders() ?? false;
    }

    public function rules(): array
    {
        return [
            'receipt_key' => ['required', 'uuid'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.item_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'between:0,1000000'],
            'items.*.damaged' => ['required', 'integer', 'between:0,1000000'],
        ];
    }
}
