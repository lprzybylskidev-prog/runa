<?php

namespace Runa\Modules\Admin\Queries;

use Runa\Integration\Core\AdminEntry;
use Runa\Integration\Core\AdminRead;
use Runa\Modules\Admin\Actions\Aggregate;
use Runa\Modules\Admin\Actions\Command;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Admin\Contracts\AdminRegistry;
use Runa\Support\Settings\ApplicationPresentation;

final readonly class Access implements AdminAccess
{
    public function __construct(private AdminEntry $entry, private AdminRead $reads, private ApplicationPresentation $presentation, private Projection $projection, private Command $commands, private Aggregate $aggregate, AdminRegistry $registry)
    {
        // Resolving the bootstrap registry runs App-owned afterResolving registrations
        // before discovery freezes them. Cold command boot never loads Admin classes.
        unset($registry);
    }

    public function entry(): array
    {
        return ['available' => $this->entry->available()];
    }

    public function bootstrap(): array
    {
        $discovery = null;

        return $this->reads->run(function () use (&$discovery): array {
            return $this->projection->widgetReadPlans($discovery);
        }, function (\Closure $invoke, array $failed) use (&$discovery): array {
            return $this->projection->bootstrap($invoke, $failed, $discovery) + [
                'presentation' => $this->presentation->read(),
                'activity' => $this->entry->activity(false),
            ];
        });
    }

    public function page(string $owner, string $page): array
    {
        return $this->entry->read(fn (): array => $this->projection->page($owner, $page));
    }

    public function read(string $owner, string $page, string $section, array $query): array
    {
        $selection = null;

        return $this->reads->run(function () use ($owner, $page, $section, $query, &$selection): array {
            return ['resource' => $this->projection->readPlan($owner, $page, $section, $query, $selection)];
        }, function (\Closure $invoke) use ($owner, $page, $section, $query, &$selection): array {
            /** @var array<string,mixed> $result */
            $result = $invoke('resource', fn (): array => $this->projection->read($owner, $page, $section, $query, $selection));

            return $result;
        });
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
