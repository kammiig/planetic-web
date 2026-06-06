<?php

namespace App\Http\Requests;

use App\Support\DomainName;
use Illuminate\Foundation\Http\FormRequest;

class DomainSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => [
                'required',
                'string',
                'max:253',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! DomainName::isValid((string) $value)) {
                        $fail('Please enter a valid domain name, for example example.co.uk.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('domain')) {
            $this->merge(['domain' => DomainName::normalise((string) $this->input('domain'))]);
        }
    }
}
