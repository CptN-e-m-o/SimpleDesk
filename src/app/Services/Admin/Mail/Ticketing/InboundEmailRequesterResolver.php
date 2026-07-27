<?php

namespace App\Services\Admin\Mail\Ticketing;

use App\Exceptions\Admin\Mail\InboundEmailTicketingException;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class InboundEmailRequesterResolver
{
    public function resolve(
        EmailMessage $emailMessage
    ): User {
        $email = strtolower(
            trim(
                (string) $emailMessage
                    ->sender_address
            )
        );

        if (
            $email === ''
            || filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new InboundEmailTicketingException(
                message:
                'Inbound email does not contain '
                . 'a valid sender address.',
                errorCode:
                'invalid_inbound_sender',
                retryable: false,
            );
        }

        $existingUser = $this->findByEmail(
            $email
        );

        if ($existingUser !== null) {
            return $existingUser;
        }

        if (
            !(bool) config(
                'simpledesk-mail-ticketing.auto_create_requesters',
                true
            )
        ) {
            throw new InboundEmailTicketingException(
                message:
                "User with email [{$email}] "
                . 'does not exist and automatic creation '
                . 'is disabled.',
                errorCode:
                'inbound_requester_not_found',
                retryable: false,
            );
        }

        [
            $firstName,
            $lastName,
        ] = $this->splitName(
            email: $email,
            senderName:
            $emailMessage->sender_name,
        );

        try {
            $user = User::query()->create([
                'email' => $email,

                'username' =>
                    $this->uniqueUsername(
                        $email
                    ),

                'first_name' => $firstName,
                'last_name' => $lastName,

                'password' => Str::random(48),

                'email_verified_at' => null,
                'is_active' => true,
            ]);
        } catch (QueryException $exception) {
            $existingUser = $this->findByEmail(
                $email
            );

            if ($existingUser !== null) {
                return $existingUser;
            }

            throw $exception;
        }

        $this->attachRequesterRole(
            $user
        );

        return $user;
    }

    private function findByEmail(
        string $email
    ): ?User {
        return User::query()
            ->withTrashed()
            ->whereRaw(
                'LOWER(email) = ?',
                [$email]
            )
            ->first();
    }

    private function splitName(
        string $email,
        ?string $senderName,
    ): array {
        $name = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $senderName)
        );

        if ($name === '') {
            $localPart = strstr(
                $email,
                '@',
                true
            );

            $name = str_replace(
                [
                    '.',
                    '_',
                    '-',
                ],
                ' ',
                $localPart !== false
                    ? $localPart
                    : 'Email requester',
            );

            $name = preg_replace(
                '/\s+/u',
                ' ',
                trim($name)
            );
        }

        $parts = preg_split(
            '/\s+/u',
            $name,
            2
        ) ?: [];

        $firstName = trim(
            (string) ($parts[0] ?? '')
        );

        $lastName = trim(
            (string) ($parts[1] ?? '')
        );

        if ($firstName === '') {
            $firstName = 'Email';
        }

        return [
            mb_substr(
                $firstName,
                0,
                100
            ),

            $lastName !== ''
                ? mb_substr(
                $lastName,
                0,
                100
            )
                : null,
        ];
    }

    private function uniqueUsername(
        string $email
    ): string {
        $localPart = strstr(
            $email,
            '@',
            true
        );

        $base = Str::ascii(
            $localPart !== false
                ? $localPart
                : 'requester'
        );

        $base = strtolower($base);

        $base = preg_replace(
            '/[^a-z0-9]+/',
            '_',
            $base
        );

        $base = trim(
            (string) $base,
            '_'
        );

        if ($base === '') {
            $base = 'requester';
        }

        $base = mb_substr(
            $base,
            0,
            40
        );

        $candidate = $base;
        $counter = 2;

        while (
        User::query()
            ->withTrashed()
            ->where(
                'username',
                $candidate
            )
            ->exists()
        ) {
            $suffix = '_' . $counter;

            $candidate =
                mb_substr(
                    $base,
                    0,
                    40 - mb_strlen($suffix)
                )
                . $suffix;

            $counter++;

            if ($counter > 1000) {
                $candidate =
                    mb_substr(
                        $base,
                        0,
                        30
                    )
                    . '_'
                    . strtolower(
                        Str::random(8)
                    );

                break;
            }
        }

        return $candidate;
    }

    private function attachRequesterRole(
        User $user
    ): void {
        $roleName = trim(
            (string) config(
                'simpledesk-mail-ticketing.requester_role',
                'user'
            )
        );

        if (
            $roleName === ''
            || $user->trashed()
        ) {
            return;
        }

        $role = Role::query()
            ->where(
                'name',
                $roleName
            )
            ->first();

        if ($role === null) {
            return;
        }

        $user
            ->roles()
            ->syncWithoutDetaching([
                $role->id,
            ]);
    }
}
