<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('active', 1)->whereNull('deleted_at')],
            'status' => ['sometimes', Rule::in(['draft', 'pending'])],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['required', 'date', 'after_or_equal:order_date'],
            'tax_rate' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'between:1,1000000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01', 'max:999999.99', 'decimal:0,2'],
        ];
    }
}
