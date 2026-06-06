<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'in:general,billing,domain,hosting,website,technical'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
