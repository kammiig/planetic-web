<x-emails.layout title="New project message">
    <h1 style="margin:0 0 8px;font-size:22px;">New message on your project</h1>
    <p style="margin:0 0 16px;color:#334155;">
        There's a new message on website project <strong>{{ $project->project_number }}</strong>
        ({{ $project->business_name ?? 'Your website' }}) from
        <strong>{{ $message->is_from_staff ? 'the Planetic Web team' : ($message->author->name ?? 'the customer') }}</strong>.
    </p>
    <blockquote style="margin:0 0 16px;padding:12px 16px;background:#f1f5f9;border-left:4px solid #2563eb;color:#334155;border-radius:4px;">
        {{ \Illuminate\Support\Str::limit($message->body, 280) }}
    </blockquote>
    @if ($toStaff)
        <x-emails.button url="{{ url('/admin/website-projects/'.$project->id.'/edit') }}">Open in admin</x-emails.button>
    @else
        <x-emails.button url="{{ url('/dashboard/website-projects/'.$project->id) }}">Reply in your dashboard</x-emails.button>
    @endif
</x-emails.layout>
