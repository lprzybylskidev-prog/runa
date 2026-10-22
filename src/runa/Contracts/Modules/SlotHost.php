<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class SlotHost implements \JsonSerializable
{
    /** Flat required scalar props, with exact named and versioned compatibility.
     * @param  array<string, string>  $props
     */
    public function __construct(public string $id, public string $contract, public int $version, public array $props = [], public int $schemaVersion = 1)
    {
        new ApiOperation($id);
        new ApiOperation($contract);
        if ($schemaVersion !== 1 || $version < 1 || count($props) > 32) {
            throw new InvalidArgumentException('Invalid slot contract.');
        }
        foreach ($props as $key => $type) {
            if (preg_match('/\A[a-z][a-zA-Z0-9]{0,63}\z/D', $key) !== 1 || in_array($key, ['key', 'ref', 'class', 'style'], true) || str_starts_with($key, 'on') || ! in_array($type, ['string', 'number', 'boolean'], true)) {
                throw new InvalidArgumentException('Invalid slot props.');
            }
        }
    }

    /** @return array{id: string, contract: string, version: int, props: object, schemaVersion: int} */
    public function jsonSerialize(): array
    {
        return ['id' => $this->id, 'contract' => $this->contract, 'version' => $this->version, 'props' => (object) $this->props, 'schemaVersion' => $this->schemaVersion];
    }
}
