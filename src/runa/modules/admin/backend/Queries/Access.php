<?php

namespace Runa\Modules\Admin\Queries;

use Runa\Contracts\Modules\OperationContext;
use Runa\Identity\ApplicationIdentity;
use Runa\Integration\Core\AdminEntry;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class Access implements AdminAccess
{
    public function __construct(private AdminEntry $entry, private ApplicationIdentity $identity) {}

    public function bootstrap(): array
    {
        return $this->entry->read(fn (OperationContext $context): array => [
            'schema_version' => 1,
            'generation' => $context->generation,
            'revision' => hash('sha256', 'admin:'.$context->generation),
            'owners' => [],
            'dashboard' => ['widgets' => []],
            'presentation' => ['name' => $this->identity->applicationName, 'logo' => $this->identity->logo, 'revision' => 0],
        ]);
    }

    public function page(string $owner, string $page): array
    {
        return $this->unavailable();
    }

    public function read(string $owner, string $page, string $section, array $query): array
    {
        return $this->unavailable();
    }

    public function preview(string $owner, string $page, array $input): array
    {
        return $this->unavailable();
    }

    public function save(string $owner, string $page, array $input): array
    {
        return $this->unavailable();
    }

    public function command(string $owner, string $page, string $section, array $input): array
    {
        return $this->unavailable();
    }

    /** Declarations and command binding arrive in subsequent points. Never fake success.
     * @return array<string, mixed>
     */
    private function unavailable(): array
    {
        return $this->entry->read(static fn () => throw new OperationRejected('admin_page_unavailable', 'runa.admin', 'page', 404));
    }
}
