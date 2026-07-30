<?php

namespace App\Services\Admin\Mail\Diagnostics;

use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Models\Admin\Mail\EmailMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MailDiagnosticsThresholds
{
    public function applyStuckMessageConstraint(
        Builder $query
    ): Builder {
        return $query->where(function (Builder $query): void {
            $query
                ->where(function (Builder $query): void {
                    $query
                        ->where('direction', 'outgoing')
                        ->where('status', 'preparing')
                        ->where(
                            'created_at',
                            '<=',
                            $this->preparingCutoff()
                        );
                })
                ->orWhere(function (Builder $query): void {
                    $query
                        ->where('direction', 'outgoing')
                        ->where('status', 'queued')
                        ->where(function (Builder $query): void {
                            $query
                                ->where(function (Builder $query): void {
                                    $query
                                        ->whereNotNull('queued_at')
                                        ->where(
                                            'queued_at',
                                            '<=',
                                            $this->queuedCutoff()
                                        );
                                })
                                ->orWhere(function (Builder $query): void {
                                    $query
                                        ->whereNull('queued_at')
                                        ->where(
                                            'created_at',
                                            '<=',
                                            $this->queuedCutoff()
                                        );
                                });
                        });
                })
                ->orWhere(function (Builder $query): void {
                    $query
                        ->where('direction', 'incoming')
                        ->where('status', 'processing')
                        ->whereNotNull('processing_started_at')
                        ->where(
                            'processing_started_at',
                            '<=',
                            $this->processingCutoff()
                        );
                })
                ->orWhere(function (Builder $query): void {
                    $query
                        ->where('direction', 'outgoing')
                        ->where('status', 'sending')
                        ->whereNotNull('processing_started_at')
                        ->where(
                            'processing_started_at',
                            '<=',
                            $this->sendingCutoff()
                        );
                });
        });
    }

    public function isMessageStuck(
        EmailMessage $message
    ): bool {
        return match (true) {
            $message->direction
            === EmailMessageDirection::Outgoing
            && $message->status
            === EmailMessageStatus::Preparing
            => $message->created_at
                ?->lessThanOrEqualTo(
                    $this->preparingCutoff()
                ) ?? false,

            $message->direction
            === EmailMessageDirection::Outgoing
            && $message->status
            === EmailMessageStatus::Queued
            => (
                $message->queued_at
                ?? $message->created_at
            )
                ?->lessThanOrEqualTo(
                    $this->queuedCutoff()
                ) ?? false,

            $message->direction
            === EmailMessageDirection::Incoming
            && $message->status
            === EmailMessageStatus::Processing
            => $message->processing_started_at
                ?->lessThanOrEqualTo(
                    $this->processingCutoff()
                ) ?? false,

            $message->direction
            === EmailMessageDirection::Outgoing
            && $message->status
            === EmailMessageStatus::Sending
            => $message->processing_started_at
                ?->lessThanOrEqualTo(
                    $this->sendingCutoff()
                ) ?? false,

            default => false,
        };
    }

    public function preparingCutoff(): Carbon
    {
        return now()->subSeconds(
            $this->seconds(
                'preparing_seconds',
                900
            )
        );
    }

    public function queuedCutoff(): Carbon
    {
        return now()->subSeconds(
            $this->seconds(
                'queued_seconds',
                900
            )
        );
    }

    public function processingCutoff(): Carbon
    {
        return now()->subSeconds(
            $this->seconds(
                'processing_seconds',
                900
            )
        );
    }

    public function sendingCutoff(): Carbon
    {
        return now()->subSeconds(
            $this->seconds(
                'sending_seconds',
                900
            )
        );
    }

    public function attachmentPendingCutoff(): Carbon
    {
        return now()->subSeconds(
            $this->seconds(
                'attachment_pending_seconds',
                900
            )
        );
    }

    public function syncCutoff(): Carbon
    {
        return now()->subSeconds(
            $this->seconds(
                'sync_seconds',
                1800
            )
        );
    }

    private function seconds(
        string $key,
        int $default
    ): int {
        return max(
            60,
            (int) config(
                "simpledesk-mail-diagnostics.stale.{$key}",
                $default
            )
        );
    }
}
