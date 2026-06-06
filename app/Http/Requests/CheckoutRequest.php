<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'billing_address_line_1' => ['required', 'string', 'max:255'],
            'billing_address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['required', 'string', 'max:120'],
            'billing_state' => ['nullable', 'string', 'max:120'],
            'billing_postcode' => ['required', 'string', 'max:20'],
            'billing_country' => ['required', 'string', 'size:2'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'Please accept the terms of use and renewal policy to continue.',
        ];
    }

    /**
     * The user-profile fields to persist before creating the order.
     *
     * @return array<string, mixed>
     */
    public function billingData(): array
    {
        return $this->safe()->only([
            'name', 'phone', 'company_name',
            'billing_address_line_1', 'billing_address_line_2', 'billing_city',
            'billing_state', 'billing_postcode', 'billing_country',
        ]);
    }
}
