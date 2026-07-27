<?php

namespace App\Console\Commands\Admin\Mail;

use App\Models\Admin\Mail\EmailMessage;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailClassifier;
use App\Services\Admin\Mail\ReplyParsing\InboundEmailReplyParser;
use Illuminate\Console\Command;

class InspectReplyParsingCommand extends Command
{
    protected $signature =
        'simpledesk:mail:inspect-reply-parsing
        {message : Incoming EmailMessage ID}';

    protected $description =
        'Classify an incoming email and preview its parsed reply body';

    public function handle(
        InboundEmailClassifier $classifier,
        InboundEmailReplyParser $parser,
    ): int {
        $emailMessage = EmailMessage::query()
            ->with('mailbox')
            ->find(
                (int) $this->argument(
                    'message'
                )
            );

        if ($emailMessage === null) {
            $this->error(
                'Email message was not found.'
            );

            return self::FAILURE;
        }

        $decision = $classifier->classify(
            $emailMessage
        );

        $content = $parser->parse(
            $emailMessage
        );

        $this->table(
            [
                'Parameter',
                'Value',
            ],
            [
                [
                    'Email message',
                    $emailMessage->id,
                ],
                [
                    'Classification',
                    $decision
                        ->classification
                        ->value,
                ],
                [
                    'Should process',
                    $decision->shouldProcess
                        ? 'yes'
                        : 'no',
                ],
                [
                    'Reason',
                    $decision->reason,
                ],
                [
                    'Content source',
                    $content->source,
                ],
                [
                    'Quoted text removed',
                    $content->quotedTextRemoved
                        ? 'yes'
                        : 'no',
                ],
                [
                    'Signature removed',
                    $content->signatureRemoved
                        ? 'yes'
                        : 'no',
                ],
                [
                    'Original length',
                    $content->originalLength,
                ],
                [
                    'Parsed length',
                    $content->parsedLength,
                ],
            ]
        );

        $this->newLine();

        $this->info(
            'Parsed reply body:'
        );

        $this->line(
            str_repeat('-', 80)
        );

        $this->line(
            $content->body
        );

        $this->line(
            str_repeat('-', 80)
        );

        return self::SUCCESS;
    }
}
