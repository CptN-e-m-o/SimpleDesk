<?php

namespace Tests\Unit\Admin\Mail;

use App\Services\Admin\Mail\UploadedMailAttachmentFactory;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UploadedMailAttachmentFactoryTest extends TestCase
{
    public function test_it_creates_attachment_data_from_an_uploaded_file(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            '../customer notes.txt',
            'hello'
        );

        $attachment = app(
            UploadedMailAttachmentFactory::class
        )->make($file);

        $this->assertSame(
            'customer notes.txt',
            $attachment->fileName
        );

        $this->assertSame(
            5,
            $attachment->size
        );

        $this->assertNotNull(
            $attachment->temporaryPath
        );

        $this->assertSame(
            'agent_email_reply_upload',
            $attachment->metadata['source']
        );
    }
}
