<?php

namespace App\Services\Admin\Mail\Settings;

use App\Models\Admin\Mail\Mailbox;
use Illuminate\Support\Facades\DB;

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

                if (!$mailbox->is_active) {
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
