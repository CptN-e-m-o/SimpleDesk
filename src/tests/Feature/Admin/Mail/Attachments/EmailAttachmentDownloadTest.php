<?php

namespace Tests\Feature\Admin\Mail\Attachments;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesMailTestData;
use Tests\TestCase;

class EmailAttachmentDownloadTest extends TestCase
{
    use CreatesMailTestData;
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        config()->set(
            'simpledesk-mail.downloads.allowed_scan_statuses',
            [
                'not_scanned',
                'clean',
            ]
        );

        config()->set(
            'simpledesk-mail.downloads.verify_checksums',
            true
        );
    }

    public function test_ticket_requester_can_download_attachment(): void
    {
        $requester =
            User::factory()->create();

        [
            $attachment,
            $contents,
        ] = $this->createStoredAttachment(
            requester: $requester,
        );

        $response = $this
            ->actingAs($requester)
            ->get(
                route(
                    'mail.attachments.download',
                    $attachment
                )
            );

        $response
            ->assertOk()
            ->assertDownload(
                'customer-notes.txt'
            )
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff'
            )
            ->assertHeader(
                'Content-Security-Policy',
                'sandbox'
            )
            ->assertHeader(
                'Cross-Origin-Resource-Policy',
                'same-origin'
            );

        $this->assertSame(
            $contents,
            $response->streamedContent()
        );
    }

    public function test_authorized_agent_can_download_attachment(): void
    {
        $requester =
            User::factory()->create();

        $agent =
            $this->createSuperAdmin();

        [
            $attachment,
            $contents,
        ] = $this->createStoredAttachment(
            requester: $requester,
            agent: $agent,
        );

        $response = $this
            ->actingAs($agent)
            ->get(
                route(
                    'mail.attachments.download',
                    $attachment
                )
            );

        $response
            ->assertOk()
            ->assertDownload(
                'customer-notes.txt'
            );

        $this->assertSame(
            $contents,
            $response->streamedContent()
        );
    }

    public function test_unrelated_user_cannot_download_attachment(): void
    {
        $requester =
            User::factory()->create();

        $unrelatedUser =
            User::factory()->create();

        [$attachment] =
            $this->createStoredAttachment(
                requester: $requester,
            );

        $this
            ->actingAs($unrelatedUser)
            ->get(
                route(
                    'mail.attachments.download',
                    $attachment
                )
            )
            ->assertNotFound();
    }

    public function test_infected_attachment_cannot_be_downloaded(): void
    {
        $requester =
            User::factory()->create();

        [$attachment] =
            $this->createStoredAttachment(
                requester: $requester,

                scanStatus: EmailAttachmentScanStatus::Infected,
            );

        $this
            ->actingAs($requester)
            ->get(
                route(
                    'mail.attachments.download',
                    $attachment
                )
            )
            ->assertNotFound();
    }

    public function test_quarantined_attachment_cannot_be_downloaded(): void
    {
        $requester =
            User::factory()->create();

        [$attachment] =
            $this->createStoredAttachment(
                requester: $requester,
            );

        $attachment->forceFill([
            'quarantined_at' => now(),
        ])->save();

        $this
            ->actingAs($requester)
            ->get(
                route(
                    'mail.attachments.download',
                    $attachment
                )
            )
            ->assertNotFound();
    }

    public function test_attachment_with_invalid_checksum_cannot_be_downloaded(): void
    {
        $requester =
            User::factory()->create();

        [$attachment] =
            $this->createStoredAttachment(
                requester: $requester,
            );

        $attachment->forceFill([
            'checksum_sha256' => hash(
                'sha256',
                'different contents'
            ),
        ])->save();

        $this
            ->actingAs($requester)
            ->get(
                route(
                    'mail.attachments.download',
                    $attachment
                )
            )
            ->assertNotFound();
    }
}
