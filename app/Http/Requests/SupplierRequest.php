<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManager() ?? false;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('suppliers')->ignore($this->route('supplier'))],
            'phone' => ['required', 'string', 'regex:/^[+0-9() .-]{7,40}$/'],
            'address' => ['required', 'string', 'max:2000'],
            'payment_terms' => ['required', 'string', 'max:60'],
            'lead_time_days' => ['required', 'integer', 'between:1,365'],
            'minimum_order_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'active' => ['required', 'boolean'],
        ];
    }
}
