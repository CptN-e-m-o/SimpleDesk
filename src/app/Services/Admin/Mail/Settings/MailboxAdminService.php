<?php

namespace App\Services\Admin\Mail\Settings;

use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MailboxAdminService
{
    public function create(array $data): Mailbox
    {
        return DB::transaction(
            function () use ($data): Mailbox {
                if ((bool) $data['is_default_outgoing']) {
                    $this->clearOtherDefaultMailboxes();
                }

                return Mailbox::query()->create([
                    'name' => trim($data['name']),
                    'email_address' => mb_strtolower(
                        trim($data['email_address'])
                    ),
                    'display_name' => $this->nullableString(
                        $data['display_name'] ?? null
                    ),
                    'department_id' => $data['department_id'] ?? null,
                    'is_active' => (bool) $data['is_active'],
                    'is_default_outgoing' => (bool) $data['is_default_outgoing'],
                    'internal_notes' => $this->nullableString(
                        $data['internal_notes'] ?? null
                    ),
                ]);
            },
            3,
        );
    }

    public function update(
        Mailbox $mailbox,
        array $data,
    ): Mailbox {
        return DB::transaction(
            function () use ($mailbox, $data): Mailbox {
                $mailbox = Mailbox::query()
                    ->lockForUpdate()
                    ->findOrFail($mailbox->id);

                if ((bool) $data['is_default_outgoing']) {
                    $this->clearOtherDefaultMailboxes(
                        $mailbox->id
                    );
                }

                $mailbox->fill([
                    'name' => trim($data['name']),
                    'email_address' => mb_strtolower(
                        trim($data['email_address'])
                    ),
                    'display_name' => $this->nullableString(
                        $data['display_name'] ?? null
                    ),
                    'department_id' => $data['department_id'] ?? null,
                    'is_active' => (bool) $data['is_active'],
                    'is_default_outgoing' => (bool) $data['is_default_outgoing'],
                    'internal_notes' => $this->nullableString(
                        $data['internal_notes'] ?? null
                    ),
                ])->save();

                if (! $mailbox->is_active) {
                    $mailbox->channels()->update([
                        'is_enabled' => false,
                        'is_primary' => false,
                    ]);
                }

                return $mailbox->fresh();
            },
            3,
        );
    }

    public function delete(Mailbox $mailbox): void
    {
        DB::transaction(
            function () use ($mailbox): void {
                $mailbox = Mailbox::query()
                    ->lockForUpdate()
                    ->findOrFail($mailbox->id);

                $mailbox->channels()->update([
                    'is_enabled' => false,
                    'is_primary' => false,
                ]);

                $mailbox->forceFill([
                    'is_active' => false,
                    'is_default_outgoing' => false,
                ])->save();

                $mailbox->delete();
            },
            3,
        );
    }

    public function restore(int $mailboxId): Mailbox
    {
        return DB::transaction(
            function () use ($mailboxId): Mailbox {
                $mailbox = Mailbox::onlyTrashed()
                    ->lockForUpdate()
                    ->findOrFail($mailboxId);

                $emailAddress = mb_strtolower(
                    trim($mailbox->email_address)
                );

                $emailAlreadyExists = Mailbox::query()
                    ->whereRaw(
                        'LOWER(email_address) = ?',
                        [$emailAddress]
                    )
                    ->exists();

                if ($emailAlreadyExists) {
                    throw ValidationException::withMessages([
                        'mailbox' => [
                            'The mailbox cannot be restored because another mailbox already uses this email address.',
                        ],
                    ]);
                }

                $mailbox->restore();

                $mailbox->forceFill([
                    'is_active' => false,
                    'is_default_outgoing' => false,
                ])->save();

                $mailbox->channels()->update([
                    'is_enabled' => false,
                    'is_primary' => false,
                ]);

                return $mailbox->fresh();
            },
            3,
        );
    }

    public function forceDelete(int $mailboxId): void
    {
        DB::transaction(
            function () use ($mailboxId): void {
                $mailbox = Mailbox::onlyTrashed()
                    ->lockForUpdate()
                    ->findOrFail($mailboxId);

                if ($mailbox->emailMessages()->exists()) {
                    throw ValidationException::withMessages([
                        'mailbox' => [
                            'A mailbox with email history cannot be permanently deleted.',
                        ],
                    ]);
                }

                $channels = $mailbox
                    ->channels()
                    ->lockForUpdate()
                    ->get();

                foreach ($channels as $channel) {
                    $this->assertChannelCanBePermanentlyDeleted(
                        $channel
                    );
                }

                foreach ($channels as $channel) {
                    $channel->syncState()->delete();
                    $channel->delete();
                }

                $mailbox->forceDelete();
            },
            3,
        );
    }

    private function assertChannelCanBePermanentlyDeleted(
        MailboxChannel $channel
    ): void {
        $hasRelatedRecords =
            $channel->emailMessages()->exists()
            || $channel->messageAttempts()->exists()
            || $channel->webhookEvents()->exists()
            || $channel->subscriptions()->exists()
            || $channel->quarantines()->exists();

        if (! $hasRelatedRecords) {
            return;
        }

        throw ValidationException::withMessages([
            'mailbox' => [
                "Mailbox channel [{$channel->name}] contains mail history or related records. The mailbox cannot be permanently deleted.",
            ],
        ]);
    }

    private function clearOtherDefaultMailboxes(
        ?int $exceptMailboxId = null
    ): void {
        Mailbox::query()
            ->when(
                $exceptMailboxId !== null,
                fn ($query) => $query->whereKeyNot(
                    $exceptMailboxId
                )
            )
            ->where('is_default_outgoing', true)
            ->update([
                'is_default_outgoing' => false,
            ]);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== ''
            ? $value
            : null;
    }
}
