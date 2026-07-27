<?php

namespace App\Services\Admin\Mail;

use App\Contracts\Admin\Mail\IncomingMailDriver;
use App\Contracts\Admin\Mail\OutgoingMailDriver;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Exceptions\Admin\Mail\InvalidMailDriverException;
use App\Exceptions\Admin\Mail\MailDriverNotRegisteredException;
use Illuminate\Contracts\Container\Container;

class MailDriverRegistry
{
    private array $resolvedIncomingDrivers = [];

    private array $resolvedOutgoingDrivers = [];

    public function __construct(
        private readonly Container $container,
        private readonly array $incomingDrivers,
        private readonly array $outgoingDrivers,
    ) {
    }

    public function incoming(
        MailboxDriver $driver
    ): IncomingMailDriver {
        if (isset($this->resolvedIncomingDrivers[$driver->value])) {
            return $this->resolvedIncomingDrivers[$driver->value];
        }

        $class = $this->incomingDrivers[$driver->value] ?? null;

        if (!is_string($class) || $class === '') {
            throw new MailDriverNotRegisteredException(
                driver: $driver,
                direction: MailboxChannelDirection::Incoming,
            );
        }

        $instance = $this->container->make($class);

        if (!$instance instanceof IncomingMailDriver) {
            throw new InvalidMailDriverException(
                "Class [{$class}] must implement "
                . IncomingMailDriver::class
                . '.'
            );
        }

        if ($instance->driver() !== $driver) {
            throw new InvalidMailDriverException(
                "Class [{$class}] returned driver "
                . "[{$instance->driver()->value}] instead of "
                . "[{$driver->value}]."
            );
        }

        $this->resolvedIncomingDrivers[$driver->value] = $instance;

        return $instance;
    }

    public function outgoing(
        MailboxDriver $driver
    ): OutgoingMailDriver {
        if (isset($this->resolvedOutgoingDrivers[$driver->value])) {
            return $this->resolvedOutgoingDrivers[$driver->value];
        }

        $class = $this->outgoingDrivers[$driver->value] ?? null;

        if (!is_string($class) || $class === '') {
            throw new MailDriverNotRegisteredException(
                driver: $driver,
                direction: MailboxChannelDirection::Outgoing,
            );
        }

        $instance = $this->container->make($class);

        if (!$instance instanceof OutgoingMailDriver) {
            throw new InvalidMailDriverException(
                "Class [{$class}] must implement "
                . OutgoingMailDriver::class
                . '.'
            );
        }

        if ($instance->driver() !== $driver) {
            throw new InvalidMailDriverException(
                "Class [{$class}] returned driver "
                . "[{$instance->driver()->value}] instead of "
                . "[{$driver->value}]."
            );
        }

        $this->resolvedOutgoingDrivers[$driver->value] = $instance;

        return $instance;
    }

    public function availableIncomingDrivers(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $driver): ?MailboxDriver =>
            MailboxDriver::tryFrom($driver),
            array_keys($this->incomingDrivers),
        )));
    }

    public function availableOutgoingDrivers(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $driver): ?MailboxDriver =>
            MailboxDriver::tryFrom($driver),
            array_keys($this->outgoingDrivers),
        )));
    }
}
