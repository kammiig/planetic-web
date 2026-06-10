<?php

namespace App\Http\Controllers\Customer;

use App\Enums\WebsiteProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebsiteProjectIntakeRequest;
use App\Models\WebsiteProject;
use App\Models\WebsiteProjectAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebsiteProjectController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.projects.index', [
            'projects' => $request->user()->websiteProjects()->with(['order', 'domain'])->latest()->get(),
        ]);
    }

    public function show(Request $request, WebsiteProject $project): View
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $project->load('assets', 'domain', 'hostingAccount');

        return view('customer.projects.show', ['project' => $project]);
    }

    public function storeIntake(WebsiteProjectIntakeRequest $request, WebsiteProject $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $project->fill([
            'business_name' => $request->validated('business_name'),
            'business_description' => $request->validated('business_description'),
            'industry' => $request->validated('industry'),
            'pages_required' => $request->validated('pages_required', []),
            'brand_colours' => $request->validated('brand_colours'),
            'reference_websites' => $request->referenceWebsites(),
            'special_requirements' => $request->validated('special_requirements'),
            'content_received' => true,
            'status' => WebsiteProjectStatus::ContentReceived->value,
        ]);

        if ($request->hasFile('logo')) {
            $this->storeAsset($project, $request->file('logo'), 'logo');
            $project->logo_received = true;
        }

        foreach ($request->file('files', []) as $file) {
            $this->storeAsset($project, $file, $this->fileTypeFor($file->getClientMimeType()));
        }

        $project->save();

        return redirect()->route('customer.projects.show', $project)
            ->with('success', 'Thanks! Your project details have been submitted — our team will get started.');
    }

    public function downloadAsset(Request $request, WebsiteProject $project, WebsiteProjectAsset $asset): StreamedResponse
    {
        // Files are private and served only through this authenticated route.
        abort_unless($project->isOwnedBy($request->user()), 404);
        abort_unless($asset->website_project_id === $project->id && $asset->isOwnedBy($request->user()), 404);

        return Storage::disk('local')->download($asset->stored_path, $asset->original_filename);
    }

    private function storeAsset(WebsiteProject $project, $file, string $type): void
    {
        // Stored outside the public web root; never directly guessable.
        $path = $file->store("website-projects/{$project->id}", 'local');

        $project->assets()->create([
            'user_id' => $project->user_id,
            'file_type' => $type,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    private function fileTypeFor(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            $mime === 'application/pdf', str_contains($mime, 'word'), $mime === 'text/plain' => 'content_document',
            default => 'other',
        };
    }
}
