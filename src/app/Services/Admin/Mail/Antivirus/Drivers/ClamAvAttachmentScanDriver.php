<?php

namespace App\Services\Admin\Mail\Antivirus\Drivers;

use App\Contracts\Admin\Mail\Antivirus\AttachmentScanDriver;
use App\Data\Admin\Mail\AttachmentScanResultData;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Exceptions\Admin\Mail\AttachmentScanException;
use Throwable;

class ClamAvAttachmentScanDriver implements AttachmentScanDriver
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly float $connectionTimeoutSeconds,
        private readonly int $readTimeoutSeconds,
        private readonly int $chunkBytes,
        private readonly int $maxStreamBytes,
    ) {}

    public function name(): string
    {
        return 'clamav';
    }

    public function testConnection(): MailConnectionTestResultData
    {
        $startedAt = hrtime(true);

        try {
            $socket = $this->connect();

            try {
                $this->writeAll(
                    socket: $socket,
                    data: "zPING\0",
                );

                $response = $this->readResponse(
                    $socket
                );
            } finally {
                fclose($socket);
            }

            $latencyMilliseconds = (int) round(
                (hrtime(true) - $startedAt) / 1_000_000
            );

            if (strcasecmp($response, 'PONG') !== 0) {
                return MailConnectionTestResultData::failure(
                    message: "Unexpected ClamAV response [{$response}].",
                    latencyMilliseconds: $latencyMilliseconds,
                    details: [
                        'driver' => $this->name(),
                        'host' => $this->host,
                        'port' => $this->port,
                        'response' => $response,
                    ],
                );
            }

            return MailConnectionTestResultData::success(
                message: 'ClamAV daemon is available.',
                latencyMilliseconds: $latencyMilliseconds,
                details: [
                    'driver' => $this->name(),
                    'host' => $this->host,
                    'port' => $this->port,
                    'response' => $response,
                ],
            );
        } catch (Throwable $exception) {
            return MailConnectionTestResultData::failure(
                message: $exception->getMessage(),
                latencyMilliseconds: (int) round(
                    (hrtime(true) - $startedAt) / 1_000_000
                ),
                details: [
                    'driver' => $this->name(),
                    'host' => $this->host,
                    'port' => $this->port,
                    'exception' => $exception::class,
                ],
            );
        }
    }

    public function scanStream(
        $stream,
        string $fileName,
        int $expectedSize,
    ): AttachmentScanResultData {
        if (! is_resource($stream)) {
            throw new AttachmentScanException(
                message: 'Attachment scan stream is not a valid resource.',
                errorCode: 'invalid_scan_stream',
                retryable: false,
                context: [
                    'file_name' => $fileName,
                ],
            );
        }

        if ($expectedSize < 0) {
            throw new AttachmentScanException(
                message: 'Attachment size cannot be negative.',
                errorCode: 'invalid_attachment_size',
                retryable: false,
                context: [
                    'file_name' => $fileName,
                    'expected_size' => $expectedSize,
                ],
            );
        }

        if ($expectedSize > $this->maxStreamBytes) {
            throw new AttachmentScanException(
                message: "Attachment [{$fileName}] exceeds the antivirus stream limit.",
                errorCode: 'antivirus_stream_limit_exceeded',
                retryable: false,
                context: [
                    'file_name' => $fileName,
                    'expected_size' => $expectedSize,
                    'max_stream_bytes' => $this->maxStreamBytes,
                ],
            );
        }

        $socket = $this->connect();
        $scannedBytes = 0;

        try {
            $this->writeAll(
                socket: $socket,
                data: "zINSTREAM\0",
            );

            while (! feof($stream)) {
                $chunk = fread(
                    $stream,
                    $this->chunkBytes
                );

                if ($chunk === false) {
                    throw new AttachmentScanException(
                        message: "Unable to read attachment [{$fileName}] for antivirus scanning.",
                        errorCode: 'attachment_stream_read_failed',
                        retryable: false,
                        context: [
                            'file_name' => $fileName,
                        ],
                    );
                }

                if ($chunk === '') {
                    continue;
                }

                $chunkLength = strlen($chunk);
                $scannedBytes += $chunkLength;

                if ($scannedBytes > $this->maxStreamBytes) {
                    throw new AttachmentScanException(
                        message: "Attachment [{$fileName}] exceeds the antivirus stream limit.",
                        errorCode: 'antivirus_stream_limit_exceeded',
                        retryable: false,
                        context: [
                            'file_name' => $fileName,
                            'scanned_bytes' => $scannedBytes,
                            'max_stream_bytes' => $this->maxStreamBytes,
                        ],
                    );
                }

                $this->writeAll(
                    socket: $socket,
                    data: pack('N', $chunkLength).$chunk,
                );
            }

            $this->writeAll(
                socket: $socket,
                data: pack('N', 0),
            );

            $response = $this->readResponse(
                $socket
            );
        } finally {
            fclose($socket);
        }

        if ($scannedBytes !== $expectedSize) {
            throw new AttachmentScanException(
                message: "Attachment [{$fileName}] size changed while it was being scanned.",
                errorCode: 'attachment_size_changed_during_scan',
                retryable: false,
                context: [
                    'file_name' => $fileName,
                    'expected_size' => $expectedSize,
                    'scanned_bytes' => $scannedBytes,
                ],
            );
        }

        return $this->parseResponse(
            response: $response,
            scannedBytes: $scannedBytes,
        );
    }

    /**
     * @return resource
     */
    private function connect()
    {
        $errorNumber = 0;
        $errorMessage = '';

        try {
            $socket = fsockopen(
                hostname: $this->host,
                port: $this->port,
                error_code: $errorNumber,
                error_message: $errorMessage,
                timeout: $this->connectionTimeoutSeconds,
            );
        } catch (Throwable $exception) {
            throw new AttachmentScanException(
                message: 'Unable to connect to the ClamAV daemon: '
                .$exception->getMessage(),
                errorCode: 'clamav_connection_failed',
                retryable: true,
                context: [
                    'host' => $this->host,
                    'port' => $this->port,
                ],
                previous: $exception,
            );
        }

        if ($socket === false) {
            throw new AttachmentScanException(
                message: 'Unable to connect to the ClamAV daemon: '
                .(
                    $errorMessage !== ''
                        ? $errorMessage
                        : "socket error {$errorNumber}"
                ),
                errorCode: 'clamav_connection_failed',
                retryable: true,
                context: [
                    'host' => $this->host,
                    'port' => $this->port,
                    'socket_error_number' => $errorNumber,
                ],
            );
        }

        stream_set_timeout(
            $socket,
            $this->readTimeoutSeconds
        );

        return $socket;
    }

    /**
     * @param  resource  $socket
     */
    private function writeAll(
        $socket,
        string $data,
    ): void {
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            $written = fwrite(
                $socket,
                substr($data, $offset)
            );

            if ($written === false || $written === 0) {
                throw new AttachmentScanException(
                    message: 'The ClamAV connection was interrupted while sending a file.',
                    errorCode: 'clamav_write_failed',
                    retryable: true,
                    context: [
                        'host' => $this->host,
                        'port' => $this->port,
                    ],
                );
            }

            $offset += $written;
        }
    }

    /**
     * @param  resource  $socket
     */
    private function readResponse($socket): string
    {
        $response = '';

        while (! feof($socket)) {
            $chunk = fread(
                $socket,
                4096
            );

            if ($chunk === false) {
                throw new AttachmentScanException(
                    message: 'Unable to read the ClamAV response.',
                    errorCode: 'clamav_read_failed',
                    retryable: true,
                );
            }

            if ($chunk === '') {
                $metadata = stream_get_meta_data(
                    $socket
                );

                if (
                    ($metadata['timed_out'] ?? false)
                    === true
                ) {
                    throw new AttachmentScanException(
                        message: 'Timed out while waiting for the ClamAV response.',
                        errorCode: 'clamav_read_timeout',
                        retryable: true,
                    );
                }

                continue;
            }

            $response .= $chunk;

            if (
                str_contains($response, "\0")
                || str_contains($response, "\n")
            ) {
                break;
            }

            if (strlen($response) > 65536) {
                throw new AttachmentScanException(
                    message: 'The ClamAV response is unexpectedly large.',
                    errorCode: 'clamav_invalid_response',
                    retryable: true,
                );
            }
        }

        $response = trim(
            rtrim(
                $response,
                "\0\r\n"
            )
        );

        if ($response === '') {
            throw new AttachmentScanException(
                message: 'ClamAV returned an empty response.',
                errorCode: 'clamav_empty_response',
                retryable: true,
            );
        }

        return $response;
    }

    protected function parseResponse(
        string $response,
        int $scannedBytes,
    ): AttachmentScanResultData {
        if (
            preg_match(
                '/^[^:]+:\s+OK$/i',
                $response
            ) === 1
        ) {
            return AttachmentScanResultData::clean(
                driver: $this->name(),
                rawResponse: $response,
                scannedBytes: $scannedBytes,
            );
        }

        if (
            preg_match(
                '/^[^:]+:\s+(.+)\s+FOUND$/i',
                $response,
                $matches
            ) === 1
        ) {
            return AttachmentScanResultData::infected(
                signature: trim($matches[1]),
                driver: $this->name(),
                rawResponse: $response,
                scannedBytes: $scannedBytes,
            );
        }

        $retryable = ! str_contains(
            strtolower($response),
            'size limit exceeded'
        );

        throw new AttachmentScanException(
            message: "ClamAV rejected the scan request: {$response}",
            errorCode: $retryable
                ? 'clamav_scan_error'
                : 'clamav_stream_limit_exceeded',
            retryable: $retryable,
            context: [
                'response' => $response,
            ],
        );
    }
}
