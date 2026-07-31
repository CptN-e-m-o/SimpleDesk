<?php

namespace Tests\Unit\Admin\Mail\Antivirus;

use App\Data\Admin\Mail\AttachmentScanResultData;
use App\Exceptions\Admin\Mail\AttachmentScanException;
use App\Services\Admin\Mail\Antivirus\Drivers\ClamAvAttachmentScanDriver;
use PHPUnit\Framework\TestCase;

class ClamAvAttachmentScanDriverTest extends TestCase
{
    public function test_it_parses_clean_response(): void
    {
        $result = $this->driver()->parseForTest(
            'stream: OK',
            12
        );

        $this->assertTrue(
            $result->clean
        );

        $this->assertNull(
            $result->signature
        );

        $this->assertSame(
            'clamav',
            $result->driver
        );

        $this->assertSame(
            12,
            $result->scannedBytes
        );
    }

    public function test_it_parses_infected_response(): void
    {
        $result = $this->driver()->parseForTest(
            'stream: Eicar-Signature FOUND',
            68
        );

        $this->assertFalse(
            $result->clean
        );

        $this->assertSame(
            'Eicar-Signature',
            $result->signature
        );

        $this->assertSame(
            68,
            $result->scannedBytes
        );
    }

    public function test_it_rejects_clamav_error_response(): void
    {
        $this->expectException(
            AttachmentScanException::class
        );

        $this->driver()->parseForTest(
            'stream: INSTREAM size limit exceeded. ERROR',
            100
        );
    }

    private function driver(): object
    {
        return new class(host: 'clamav', port: 3310, connectionTimeoutSeconds: 1, readTimeoutSeconds: 1, chunkBytes: 8192, maxStreamBytes: 1024) extends ClamAvAttachmentScanDriver
        {
            public function parseForTest(
                string $response,
                int $scannedBytes,
            ): AttachmentScanResultData {
                return $this->parseResponse(
                    response: $response,
                    scannedBytes: $scannedBytes,
                );
            }
        };
    }
}
