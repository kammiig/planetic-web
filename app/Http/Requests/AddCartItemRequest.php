<?php

namespace App\Http\Requests;

use App\Enums\ItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_type' => [
                'required',
                Rule::in([
                    ItemType::WebsitePackage->value,
                    ItemType::Hosting->value,
                    ItemType::DomainRegistration->value,
                ]),
            ],
            'product_id' => [
                Rule::requiredIf(fn () => $this->input('item_type') === ItemType::Hosting->value),
                'nullable',
                'integer',
                'exists:products,id',
            ],
            'domain_name' => [
                Rule::requiredIf(fn () => $this->input('item_type') === ItemType::DomainRegistration->value),
                'nullable',
                'string',
                'max:253',
            ],
            'billing_cycle' => ['nullable', Rule::in(['one_time', 'monthly', 'yearly'])],
        ];
    }
}
