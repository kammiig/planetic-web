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
            ...self::attachmentRules(),
        ];
    }

    /**
     * Shared attachment rules (ticket creation + replies). Whitelist only —
     * executables and scripts are rejected outright, and files are stored under
     * a hashed name on the private disk so nothing uploaded is ever executable
     * or directly web-accessible.
     *
     * @return array<string, array<int, string>>
     */
    public static function attachmentRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240', // 10 MB per file
                'extensions:jpg,jpeg,png,pdf,doc,docx,txt,zip',
                'mimes:jpg,jpeg,png,pdf,doc,docx,txt,zip',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.max' => 'You can attach up to 5 files per message.',
            'attachments.*.max' => 'Each attachment must be 10 MB or smaller.',
            'attachments.*.extensions' => 'Allowed file types: jpg, png, pdf, doc, docx, txt, zip.',
            'attachments.*.mimes' => 'Allowed file types: jpg, png, pdf, doc, docx, txt, zip.',
        ];
    }
}
