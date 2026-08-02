<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\MailboxChannels\StoreMailboxChannelRequest;
use App\Http\Requests\Admin\Mail\MailboxChannels\UpdateMailboxChannelRequest;
use App\Http\Resources\Admin\Mail\MailboxChannelResource;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\Settings\MailboxChannelAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class MailboxChannelController extends Controller
{
    public function __construct(
        private readonly MailboxChannelAdminService $channels,
    ) {}

    public function index(
        Mailbox $mailbox
    ): AnonymousResourceCollection {
        return MailboxChannelResource::collection(
            $mailbox
                ->channels()
                ->with(
                    'providerConnection:id,name,provider,is_active'
                )
                ->orderBy('direction')
                ->orderByDesc('is_primary')
                ->orderBy('failover_order')
                ->orderBy('id')
                ->get()
        );
    }

    public function store(
        StoreMailboxChannelRequest $request,
        Mailbox $mailbox,
    ): JsonResponse {
        $channel = $this->channels->create(
            mailbox: $mailbox,
            data: $request->validated(),
        );

        $channel->load(
            'providerConnection:id,name,provider,is_active'
        );

        return MailboxChannelResource::make($channel)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(
        MailboxChannel $channel
    ): MailboxChannelResource {
        $channel->load(
            'providerConnection:id,name,provider,is_active'
        );

        return MailboxChannelResource::make($channel);
    }

    public function update(
        UpdateMailboxChannelRequest $request,
        MailboxChannel $channel,
    ): MailboxChannelResource {
        $channel = $this->channels->update(
            channel: $channel,
            data: $request->validated(),
        );

        $channel->load(
            'providerConnection:id,name,provider,is_active'
        );

        return MailboxChannelResource::make($channel);
    }

    public function destroy(
        MailboxChannel $channel
    ): Response {
        $this->channels->delete(
            $channel
        );

        return response()->noContent();
    }
}
