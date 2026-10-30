<?php

namespace Runa\Modules\Admin\Queries;

use Runa\Contracts\Modules\OperationContext;
use Runa\Identity\ApplicationIdentity;
use Runa\Integration\Core\AdminEntry;
use Runa\Modules\Admin\Actions\Aggregate;
use Runa\Modules\Admin\Actions\Command;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Admin\Contracts\AdminRegistry;

final readonly class Access implements AdminAccess
{
    public function __construct(private AdminEntry $entry, private ApplicationIdentity $identity, private Projection $projection, private Command $commands, private Aggregate $aggregate, AdminRegistry $registry)
    {
        // Resolving the bootstrap registry runs App-owned afterResolving registrations
        // before discovery freezes them. Cold command boot never loads Admin classes.
    }

    public function entry(): array
    {
        return ['available' => $this->entry->available()];
    }

    public function bootstrap(): array
    {
        return $this->entry->read(fn (OperationContext $context): array => $this->projection->bootstrap() + [
            'presentation' => ['name' => $this->identity->applicationName, 'logo' => $this->identity->logo, 'revision' => 0],
        ]);
    }

    public function page(string $owner, string $page): array
    {
        return $this->entry->read(fn (): array => $this->projection->page($owner, $page));
    }

    public function read(string $owner, string $page, string $section, array $query): array
    {
        return $this->entry->read(fn (): array => $this->projection->read($owner, $page, $section, $query));
    }

    public function preview(string $owner, string $page, array $input): array
    {
        return $this->aggregate->run($owner, $page, $input, true);
    }

    public function save(string $owner, string $page, array $input): array
    {
        return $this->aggregate->run($owner, $page, $input);
    }

    public function command(string $owner, string $page, string $section, array $input): array
    {
        return $this->commands->run($owner, $page, $section, $input);
    }

    public function previewCommand(string $owner, string $page, string $section, array $input): array
    {
        return $this->commands->run($owner, $page, $section, $input, true);
    }
}
