<?php

namespace App\Http\Controllers\Admin\Mail\OAuth;

use App\Enums\Admin\Mail\MailProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\OAuth\MailOAuthIntegrationRequest;
use App\Http\Resources\Admin\Mail\MailOAuthIntegrationResource;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\OAuth\MailOAuthIntegrationService;
use App\Services\Admin\Mail\OAuth\MailOAuthProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailOAuthIntegrationController extends Controller
{
    public function __construct(
        private readonly MailOAuthIntegrationService $integrations,
        private readonly MailOAuthProviderRegistry $providers,
    ) {}

    public function index(Request $request): Response
    {
        $connections = MailProviderConnection::query()->withTrashed()->withCount('channels')
            ->whereIn('provider', [MailProvider::Google, MailProvider::Microsoft])
            ->where('auth_type', 'oauth2')->orderByRaw('deleted_at IS NOT NULL')->orderBy('name')->get();

        return Inertia::render('Admin/Email/OAuthIntegrations/Index', [
            'integrations' => MailOAuthIntegrationResource::collection($connections)->resolve($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Email/OAuthIntegrations/Create', $this->formProps());
    }

    public function store(MailOAuthIntegrationRequest $request): RedirectResponse
    {
        $connection = $this->integrations->create($request->validated());

        return redirect()->route('admin.email.oauth-integrations.edit', $connection)->with('success', 'OAuth integration created.');
    }

    public function edit(Request $request, MailProviderConnection $connection): Response
    {
        $connection->loadCount('channels');

        return Inertia::render('Admin/Email/OAuthIntegrations/Edit', array_merge($this->formProps(), [
            'integration' => MailOAuthIntegrationResource::make($connection)->resolve($request),
        ]));
    }

    public function update(MailOAuthIntegrationRequest $request, MailProviderConnection $connection): RedirectResponse
    {
        $this->integrations->update($connection, $request->validated());

        return back()->with('success', 'OAuth integration updated.');
    }

    public function destroy(MailProviderConnection $connection): RedirectResponse
    {
        $this->integrations->delete($connection);

        return redirect()->route('admin.email.oauth-integrations.index')->with('success', 'OAuth integration deleted.');
    }

    public function restore(int $connection): RedirectResponse
    {
        $this->integrations->restore($connection);

        return back()->with('success', 'OAuth integration restored in disabled state.');
    }

    public function forceDestroy(int $connection): RedirectResponse
    {
        $this->integrations->forceDelete($connection);

        return back()->with('success', 'OAuth integration permanently deleted.');
    }

    private function formProps(): array
    {
        return [
            'redirect_url' => route('admin.email.oauth-integrations.callback'),
            'provider_scopes' => [
                'google' => $this->providers->resolve(MailProvider::Google)->requiredScopes(),
                'microsoft' => $this->providers->resolve(MailProvider::Microsoft)->requiredScopes(),
            ],
        ];
    }
}
