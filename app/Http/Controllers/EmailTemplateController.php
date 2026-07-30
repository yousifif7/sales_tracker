<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Support\HtmlContent;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permissions::EMAIL_TEMPLATES_VIEW);

        return view('email-templates.index', [
            'templates' => EmailTemplate::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission(Permissions::EMAIL_TEMPLATES_CREATE);

        return view('email-templates.create', [
            'template' => new EmailTemplate(['is_active' => true]),
        ]);
    }

    public function store(EmailTemplateRequest $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAIL_TEMPLATES_CREATE);

        $data = $this->payload($request);

        EmailTemplate::query()->create($data);

        return redirect()
            ->route('email-templates.index')
            ->with('status', 'Email template created.');
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        $this->authorizePermission(Permissions::EMAIL_TEMPLATES_UPDATE);

        return view('email-templates.edit', [
            'template' => $emailTemplate,
        ]);
    }

    public function update(EmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAIL_TEMPLATES_UPDATE);

        $emailTemplate->update($this->payload($request, $emailTemplate));

        return redirect()
            ->route('email-templates.index')
            ->with('status', 'Email template updated.');
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAIL_TEMPLATES_DELETE);

        $emailTemplate->delete();

        return redirect()
            ->route('email-templates.index')
            ->with('status', 'Email template deleted.');
    }

    /**
     * @return array{name: string, slug: string, subject: string, body: string, is_active: bool}
     */
    protected function payload(EmailTemplateRequest $request, ?EmailTemplate $existing = null): array
    {
        $name = $request->validated('name');
        $slug = Str::slug($request->validated('slug') ?: $name);

        if (blank($slug)) {
            $slug = 'template-'.Str::random(6);
        }

        $uniqueSlug = $slug;
        $suffix = 1;

        while (
            EmailTemplate::query()
                ->where('slug', $uniqueSlug)
                ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                ->exists()
        ) {
            $uniqueSlug = $slug.'-'.$suffix;
            $suffix++;
        }

        return [
            'name' => $name,
            'slug' => $uniqueSlug,
            'subject' => $request->validated('subject'),
            'body' => HtmlContent::sanitize($request->validated('body')),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
