<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }
    protected function prepareForValidation(): void
    {
        $this->merge(['sku' => strtoupper(trim((string)$this->input('sku')))]);
    }
    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'sku' => ['required','string','max:60','regex:/^[A-Z0-9-]+$/',Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'description' => ['nullable','string','max:5000'],
            'category' => ['required',Rule::in(Product::CATEGORIES)],
            'quantity' => ['required','integer','min:0','max:2147483647'],
            'reorder_level' => ['required','integer','min:0','max:2147483647'],
            'unit_price' => ['required','numeric','min:0','max:99999999.99','decimal:0,2'],
            'supplier' => ['nullable','string','max:255'],
            'supplier_id' => ['nullable',Rule::exists('suppliers', 'id')->where('active', 1)->whereNull('deleted_at')],
            'safety_stock' => ['sometimes','required','integer','between:0,1000000'],
            'target_stock' => ['sometimes','required','integer','between:1,1000000'],
            'daily_usage_estimate' => ['sometimes','required','numeric','between:0,100000','decimal:0,2'],
        ];
    }
    public function messages(): array
    {
        return ['sku.unique' => 'This SKU already belongs to another product.',
            'sku.regex' => 'Use letters, numbers, and hyphens for the SKU.',
            'quantity.min' => 'Stock quantity cannot be negative.',
            'unit_price.min' => 'Unit price cannot be negative.',
            'unit_price.decimal' => 'Use no more than two decimal places for prices.'];
    }
}
