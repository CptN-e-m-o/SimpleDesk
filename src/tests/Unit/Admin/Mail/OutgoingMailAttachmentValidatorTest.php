<?php

namespace Tests\Unit\Admin\Mail;

use App\Data\Admin\Mail\MailAttachmentData;
use App\Exceptions\Admin\Mail\MailStorageException;
use App\Services\Admin\Mail\OutgoingMailAttachmentValidator;
use PHPUnit\Framework\TestCase;

class OutgoingMailAttachmentValidatorTest extends TestCase
{
    public function test_it_accepts_allowed_attachments_within_limits(): void
    {
        $validator = new OutgoingMailAttachmentValidator(
            allowedMimeTypes: [
                'text/plain',
                'image/*',
            ],
            maxAttachmentCount: 2,
            maxAttachmentBytes: 10,
            maxTotalAttachmentBytes: 20,
        );

        $validator->validate([
            new MailAttachmentData(
                fileName: 'notes.txt',
                mimeType: 'text/plain',
                content: 'hello',
            ),
            new MailAttachmentData(
                fileName: 'image.png',
                mimeType: 'image/png',
                content: 'image',
            ),
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_it_rejects_a_disallowed_mime_type(): void
    {
        $validator = new OutgoingMailAttachmentValidator(
            allowedMimeTypes: [
                'application/pdf',
            ],
            maxAttachmentCount: 2,
            maxAttachmentBytes: 100,
            maxTotalAttachmentBytes: 200,
        );

        $this->expectException(
            MailStorageException::class
        );

        $validator->validate([
            new MailAttachmentData(
                fileName: 'script.php',
                mimeType: 'text/x-php',
                content: '<?php echo 1;',
            ),
        ]);
    }

    public function test_it_rejects_an_attachment_over_the_individual_limit(): void
    {
        $validator = new OutgoingMailAttachmentValidator(
            allowedMimeTypes: [
                'text/plain',
            ],
            maxAttachmentCount: 2,
            maxAttachmentBytes: 4,
            maxTotalAttachmentBytes: 20,
        );

        $this->expectException(
            MailStorageException::class
        );

        $validator->validate([
            new MailAttachmentData(
                fileName: 'notes.txt',
                mimeType: 'text/plain',
                content: 'hello',
            ),
        ]);
    }

    public function test_it_rejects_attachments_over_the_total_limit(): void
    {
        $validator = new OutgoingMailAttachmentValidator(
            allowedMimeTypes: [
                'text/plain',
            ],
            maxAttachmentCount: 3,
            maxAttachmentBytes: 10,
            maxTotalAttachmentBytes: 8,
        );

        $this->expectException(
            MailStorageException::class
        );

        $validator->validate([
            new MailAttachmentData(
                fileName: 'one.txt',
                mimeType: 'text/plain',
                content: '12345',
            ),
            new MailAttachmentData(
                fileName: 'two.txt',
                mimeType: 'text/plain',
                content: '67890',
            ),
        ]);
    }
}
