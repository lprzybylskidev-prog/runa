<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;
use Runa\Support\Modules\Api\BoundaryValues;
use Runa\Support\Modules\ModuleIdentity;

final readonly class PublicEvent
{
    public int $schemaVersion;

    /** Payload contains public JSON values only; the publisher owns its documented schema.
     * @param  array<array-key, mixed>  $payload
     */
    public function __construct(public string $id, public string $module, public string $event, public int $version, public string $correlationId, public array $payload)
    {
        new EventDefinition($event, $version);
        new ModuleIdentity($module);
        BoundaryValues::check($payload, "\0");
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', $id) !== 1 || preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/D', $correlationId) !== 1 || strlen(json_encode($payload, JSON_THROW_ON_ERROR)) > 60000) {
            throw new InvalidArgumentException('Invalid public fact envelope.');
        }
        $this->schemaVersion = 1;
    }

    /** @return array{schema_version: int, id: string, module: string, event: string, version: int, correlation_id: string, payload: array<array-key, mixed>} */
    public function toArray(): array
    {
        return ['schema_version' => 1, 'id' => $this->id, 'module' => $this->module, 'event' => $this->event, 'version' => $this->version, 'correlation_id' => $this->correlationId, 'payload' => $this->payload];
    }

    /** @param array<array-key, mixed> $value */
    public static function fromArray(array $value): self
    {
        if (($value['schema_version'] ?? null) !== 1 || count($value) !== 7 || ! is_string($value['id'] ?? null) || ! is_string($value['module'] ?? null) || ! is_string($value['event'] ?? null) || ! is_int($value['version'] ?? null) || ! is_string($value['correlation_id'] ?? null) || ! is_array($value['payload'] ?? null)) {
            throw new InvalidArgumentException('Invalid stored fact envelope.');
        }

        return new self($value['id'], $value['module'], $value['event'], $value['version'], $value['correlation_id'], $value['payload']);
    }
}
