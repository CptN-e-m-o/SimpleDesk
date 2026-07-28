<?php

namespace App\Services\Admin\Mail;

use App\Data\Admin\Mail\MailAttachmentData;
use App\Exceptions\Admin\Mail\MailStorageException;
use Illuminate\Http\UploadedFile;

class UploadedMailAttachmentFactory
{
    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, MailAttachmentData>
     */
    public function makeMany(array $files): array
    {
        return array_map(
            fn (UploadedFile $file): MailAttachmentData =>
            $this->make($file),
            array_values($files),
        );
    }

    public function make(
        UploadedFile $file
    ): MailAttachmentData {
        if (!$file->isValid()) {
            throw new MailStorageException(
                'Uploaded attachment is not valid.'
            );
        }

        $temporaryPath = $file->getRealPath();

        if (
            $temporaryPath === false
            || !is_file($temporaryPath)
            || !is_readable($temporaryPath)
        ) {
            throw new MailStorageException(
                'Uploaded attachment cannot be read.'
            );
        }

        $size = $file->getSize();

        if ($size === false) {
            throw new MailStorageException(
                'Uploaded attachment size cannot be determined.'
            );
        }

        $mimeType = $file->getMimeType();

        if (
            !is_string($mimeType)
            || trim($mimeType) === ''
        ) {
            $mimeType = 'application/octet-stream';
        }

        return new MailAttachmentData(
            fileName: $this->safeFileName(
                $file->getClientOriginalName()
            ),
            mimeType: strtolower(trim($mimeType)),
            size: $size,
            temporaryPath: $temporaryPath,
            metadata: [
                'source' => 'agent_email_reply_upload',
            ],
        );
    }

    private function safeFileName(
        string $fileName
    ): string {
        $fileName = basename(
            str_replace('\\', '/', $fileName)
        );

        $fileName = preg_replace(
            '/[\x00-\x1F\x7F]+/u',
            ' ',
            $fileName
        );

        $fileName = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $fileName)
        );

        if ($fileName === '') {
            return 'attachment.bin';
        }

        return mb_substr(
            $fileName,
            0,
            255
        );
    }
}
