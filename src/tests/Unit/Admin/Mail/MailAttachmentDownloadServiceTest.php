<?php

namespace Tests\Unit\Admin\Mail;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Exceptions\Admin\Mail\MailStorageException;
use App\Models\Admin\Mail\EmailAttachment;
use App\Services\Admin\Mail\MailAttachmentDownloadService;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MailAttachmentDownloadServiceTest extends TestCase
{
    public function test_it_returns_a_private_attachment_download(): void
    {
        Storage::fake('mail-test');

        $contents = 'download contents';
        $path = 'mail/attachments/file.txt';

        Storage::disk('mail-test')->put(
            $path,
            $contents
        );

        $attachment = new EmailAttachment;

        $attachment->forceFill([
            'file_name' => 'file.txt',
            'mime_type' => 'text/plain',
            'size' => strlen($contents),
            'disk' => 'mail-test',
            'path' => $path,
            'checksum_sha256' => hash(
                'sha256',
                $contents
            ),
            'scan_status' => EmailAttachmentScanStatus::Clean,
            'quarantined_at' => null,
        ]);

        $service = new MailAttachmentDownloadService(
            filesystem: app(
                FilesystemFactory::class
            ),
            allowedScanStatuses: [
                'not_scanned',
                'clean',
            ],
            verifyChecksums: true,
        );

        $response = $service->download(
            $attachment
        );

        $cacheControl = $response->headers->get(
            'Cache-Control'
        );

        $this->assertNotNull($cacheControl);

        $cacheControlDirectives = array_map(
            'trim',
            explode(',', $cacheControl)
        );

        $this->assertEqualsCanonicalizing(
            [
                'private',
                'no-store',
                'max-age=0',
            ],
            $cacheControlDirectives
        );

        $this->assertSame(
            'nosniff',
            $response->headers->get(
                'X-Content-Type-Options'
            )
        );

        $this->assertSame(
            'sandbox',
            $response->headers->get(
                'Content-Security-Policy'
            )
        );

        $this->assertSame(
            'same-origin',
            $response->headers->get(
                'Cross-Origin-Resource-Policy'
            )
        );

        $this->assertSame(
            'text/plain',
            $response->headers->get(
                'Content-Type'
            )
        );

        ob_start();

        try {
            $response->sendContent();

            $downloaded = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertSame(
            $contents,
            $downloaded
        );
    }

    public function test_it_rejects_a_quarantined_attachment(): void
    {
        $attachment = new EmailAttachment;

        $attachment->forceFill([
            'file_name' => 'file.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'disk' => 'mail-test',
            'path' => 'file.txt',
            'checksum_sha256' => hash(
                'sha256',
                'x'
            ),
            'scan_status' => EmailAttachmentScanStatus::Clean,
            'quarantined_at' => now(),
        ]);

        $service = new MailAttachmentDownloadService(
            filesystem: app(
                FilesystemFactory::class
            ),
            allowedScanStatuses: [
                'not_scanned',
                'clean',
            ],
            verifyChecksums: true,
        );

        $this->expectException(
            MailStorageException::class
        );

        $service->download(
            $attachment
        );
    }
}
