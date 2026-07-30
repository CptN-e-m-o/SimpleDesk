<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\Mailboxes\StoreMailboxRequest;
use App\Http\Requests\Admin\Mail\Mailboxes\UpdateMailboxRequest;
use App\Http\Resources\Admin\Mail\MailboxResource;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\Settings\MailboxAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class MailboxController extends Controller
{
    public function __construct(
        private readonly MailboxAdminService $mailboxes,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Mailbox::query()
            ->with('department:id,name')
            ->withCount([
                'channels',
                'emailMessages',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(
                function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email_address', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%");
                }
            );
        }

        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        return MailboxResource::collection(
            $query
                ->orderByDesc('is_default_outgoing')
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
        StoreMailboxRequest $request
    ): JsonResponse {
        $mailbox = $this->mailboxes->create(
            $request->validated()
        );

        $mailbox->load('department:id,name');
        $mailbox->loadCount([
            'channels',
            'emailMessages',
        ]);

        return MailboxResource::make($mailbox)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Mailbox $mailbox): MailboxResource
    {
        $mailbox->load([
            'department:id,name',
            'channels' => fn ($query) => $query
                ->with('providerConnection:id,name,provider,is_active')
                ->orderBy('direction')
                ->orderByDesc('is_primary')
                ->orderBy('failover_order')
                ->orderBy('id'),
        ]);

        $mailbox->loadCount([
            'channels',
            'emailMessages',
        ]);

        return MailboxResource::make($mailbox);
    }

    public function update(
        UpdateMailboxRequest $request,
        Mailbox $mailbox,
    ): MailboxResource {
        $mailbox = $this->mailboxes->update(
            mailbox: $mailbox,
            data: $request->validated(),
        );

        $mailbox->load('department:id,name');
        $mailbox->loadCount([
            'channels',
            'emailMessages',
        ]);

        return MailboxResource::make($mailbox);
    }

    public function destroy(Mailbox $mailbox): Response
    {
        $this->mailboxes->delete(
            $mailbox
        );

        return response()->noContent();
    }
}
