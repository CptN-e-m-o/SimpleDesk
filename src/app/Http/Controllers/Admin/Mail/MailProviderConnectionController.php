<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\ProviderConnections\StoreMailProviderConnectionRequest;
use App\Http\Requests\Admin\Mail\ProviderConnections\UpdateMailProviderConnectionRequest;
use App\Http\Resources\Admin\Mail\MailProviderConnectionResource;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\Settings\MailProviderConnectionAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class MailProviderConnectionController extends Controller
{
    public function __construct(
        private readonly MailProviderConnectionAdminService $connections,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MailProviderConnection::query()
            ->withCount('channels');

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(
                function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('account_identifier', 'like', "%{$search}%")
                        ->orWhere('tenant_identifier', 'like', "%{$search}%");
                }
            );
        }

        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        if ($request->filled('provider')) {
            $query->where(
                'provider',
                (string) $request->string('provider')
            );
        }

        return MailProviderConnectionResource::collection(
            $query
                ->orderBy('name')
                ->paginate(
                    perPage: min(
                        100,
                        max(
                            1,
                            $request->integer('per_page', 25)
                        )
                    )
                )
                ->withQueryString()
        );
    }

    public function store(
        StoreMailProviderConnectionRequest $request
    ): JsonResponse {
        $connection = $this->connections->create(
            $request->validated()
        );

        $connection->loadCount('channels');

        return MailProviderConnectionResource::make($connection)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(
        MailProviderConnection $providerConnection
    ): MailProviderConnectionResource {
        $providerConnection->loadCount('channels');

        return MailProviderConnectionResource::make(
            $providerConnection
        );
    }

    public function update(
        UpdateMailProviderConnectionRequest $request,
        MailProviderConnection $providerConnection,
    ): MailProviderConnectionResource {
        $providerConnection = $this->connections->update(
            connection: $providerConnection,
            data: $request->validated(),
        );

        $providerConnection->loadCount('channels');

        return MailProviderConnectionResource::make(
            $providerConnection
        );
    }

    public function destroy(
        MailProviderConnection $providerConnection
    ): Response {
        $this->connections->delete(
            $providerConnection
        );

        return response()->noContent();
    }
}
