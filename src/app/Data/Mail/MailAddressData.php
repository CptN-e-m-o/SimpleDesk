<?php

namespace App\Data\Mail;

use InvalidArgumentException;
use JsonSerializable;

final readonly class MailAddressData implements JsonSerializable
{
    public string $address;

    public ?string $name;

    public function __construct(
        string $address,
        ?string $name = null,
    ) {
        $address = trim($address);
        $name = $name !== null ? trim($name) : null;

        if ($address === '') {
            throw new InvalidArgumentException(
                'Mail address cannot be empty.'
            );
        }

        $this->address = $address;
        $this->name = $name !== '' ? $name : null;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            address: (string) ($data['address'] ?? ''),
            name: isset($data['name'])
                ? (string) $data['name']
                : null,
        );
    }

    /**
     * @return array<int, self>
     */
    public static function collection(array $items): array
    {
        return array_values(array_map(
            static function (mixed $item): self {
                if ($item instanceof self) {
                    return $item;
                }

                if (is_string($item)) {
                    return new self($item);
                }

                if (is_array($item)) {
                    return self::fromArray($item);
                }

                throw new InvalidArgumentException(
                    'Invalid mail address representation.'
                );
            },
            $items,
        ));
    }

    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'name' => $this->name,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
