<?php

namespace Tests\Fakes\Admin\Mail;

use App\Contracts\Admin\Mail\Antivirus\AttachmentScanDriver;
use App\Data\Admin\Mail\AttachmentScanResultData;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Exceptions\Admin\Mail\AttachmentScanException;

class FakeAttachmentScanDriver implements AttachmentScanDriver
{
    /**
     * @var array<int, AttachmentScanResultData|AttachmentScanException>
     */
    private array $responses = [];

    public array $scans = [];

    public function name(): string
    {
        return 'fake-antivirus';
    }

    public function testConnection(): MailConnectionTestResultData
    {
        return MailConnectionTestResultData::success(
            message: 'Fake antivirus is available.',
            latencyMilliseconds: 0,
            details: [
                'driver' => $this->name(),
            ],
        );
    }

    public function pushResult(
        AttachmentScanResultData $result
    ): void {
        $this->responses[] = $result;
    }

    public function pushException(
        AttachmentScanException $exception
    ): void {
        $this->responses[] = $exception;
    }

    public function scanStream(
        $stream,
        string $fileName,
        int $expectedSize,
    ): AttachmentScanResultData {
        $contents = stream_get_contents(
            $stream
        );

        if ($contents === false) {
            throw new AttachmentScanException(
                message: 'Fake scanner could not read the stream.',
                errorCode: 'fake_stream_read_failed',
                retryable: false,
            );
        }

        $this->scans[] = [
            'file_name' => $fileName,
            'expected_size' => $expectedSize,
            'contents' => $contents,
        ];

        $response = array_shift(
            $this->responses
        );

        if ($response instanceof AttachmentScanException) {
            throw $response;
        }

        return $response
            ?? AttachmentScanResultData::clean(
                driver: $this->name(),
                rawResponse: 'fake: OK',
                scannedBytes: strlen($contents),
            );
    }
}
