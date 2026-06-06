<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebsiteProjectIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Allowed upload types only (Security & Access §11.11). Dangerous types
        // (php, exe, js, sh, bat, html, svg) are excluded from the whitelist.
        $allowed = 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt';

        return [
            'business_name' => ['required', 'string', 'max:255'],
            'business_description' => ['nullable', 'string', 'max:5000'],
            'industry' => ['nullable', 'string', 'max:255'],
            'pages_required' => ['nullable', 'array'],
            'pages_required.*' => ['string', 'max:120'],
            'brand_colours' => ['nullable', 'string', 'max:255'],
            'reference_websites' => ['nullable', 'string', 'max:2000'],
            'special_requirements' => ['nullable', 'string', 'max:5000'],
            'logo' => ['nullable', 'file', $allowed, 'max:5120'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', $allowed, 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.mimes' => 'Your logo must be a JPG, PNG, WEBP or PDF file.',
            'files.*.mimes' => 'Uploads must be JPG, PNG, WEBP, PDF, DOC, DOCX or TXT files.',
            'files.*.max' => 'Each file must be 10 MB or smaller.',
        ];
    }

    /** @return array<int, string> Reference website URLs, one per line. */
    public function referenceWebsites(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->input('reference_websites')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
