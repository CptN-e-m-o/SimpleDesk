<?php

namespace App\Data\Admin\System\Storage;

final readonly class StorageActivationResultData
{
    public function __construct(public bool $restartSignaled, public ?string $warning = null) {}
}
