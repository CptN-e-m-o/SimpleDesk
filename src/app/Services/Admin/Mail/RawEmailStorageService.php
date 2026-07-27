<?php

namespace App\Services\Admin\Mail;

use App\Exceptions\Admin\Mail\MailStorageException;
use App\Models\Admin\Mail\EmailMessage;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Str;
use Throwable;

class RawEmailStorageService
{
    public function __construct(
        private readonly Factory $filesystem,
        private readonly string $disk,
        private readonly string $rootPath,
    ) {
    }

    public function store(
        EmailMessage $emailMessage,
        string $rawMessage,
    ): void {
        if ($emailMessage->raw_message_path !== null) {
            return;
        }

        $checksum = hash('sha256', $rawMessage);
        $size = strlen($rawMessage);

        $createdAt = $emailMessage->created_at ?? now();

        $path = implode('/', [
            trim($this->rootPath, '/'),
            $createdAt->format('Y'),
            $createdAt->format('m'),
            "{$emailMessage->id}-" . Str::uuid() . '.eml',
        ]);

        $storage = $this->filesystem->disk($this->disk);

        if (!$storage->put($path, $rawMessage)) {
            throw new MailStorageException(
                "Unable to store raw email message "
                . "[{$emailMessage->id}]."
            );
        }

        try {
            $emailMessage->forceFill([
                'raw_message_disk' => $this->disk,
                'raw_message_path' => $path,
                'raw_message_size' => $size,
                'raw_message_checksum' => $checksum,
            ])->save();
        } catch (Throwable $exception) {
            $storage->delete($path);

            throw $exception;
        }
    }
}
