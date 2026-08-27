<?php

namespace App\Services\Admin\System\Search;

class SearchRuntimeState
{
    private ?string $driver = null;

    public function setDriver(string $driver): void
    {
        $this->driver = $driver;
    }

    public function driver(): string
    {
        if ($this->driver === null) {
            throw new \RuntimeException('The managed Search runtime driver has not been resolved.');
        }

        return $this->driver;
    }
}
