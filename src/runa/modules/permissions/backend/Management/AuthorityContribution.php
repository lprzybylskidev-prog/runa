<?php

namespace Runa\Modules\Permissions\Management;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementApiTarget;
use Runa\Contracts\Modules\ManagementBinding;
use Runa\Contracts\Modules\ManagementCommandDefinition;
use Runa\Contracts\Modules\ManagementContribution;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementFieldAccess;
use Runa\Contracts\Modules\ManagementOperation;
use Runa\Contracts\Modules\ManagementPageProvider;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Modules\Permissions\Contracts\PermissionsAuthority;
use Runa\Modules\Permissions\Contracts\PermissionsAuthorityBatch;
use Runa\Modules\Permissions\Contracts\PermissionsSelections;

final class AuthorityContribution implements ManagementPageProvider
{
    public function permissions(): array
    {
        return [];
    }

    public function actions(): array
    {
        return [];
    }

    public function components(): array
    {
        return [];
    }

    public function pages(): array
    {
        return [];
    }

    public function widgets(): array
    {
        return [];
    }

    public function contributions(): array
    {
        $grant = 'runa.permissions.authority.manage';
        $commands = [];
        foreach (['grant_superadmin' => ['pl' => 'Nadaj superadministratora', 'en' => 'Grant super administrator'], 'revoke_superadmin' => ['pl' => 'Odbierz superadministratora', 'en' => 'Revoke super administrator']] as $key => $title) {
            $commands[] = new ManagementCommandDefinition($key, $title, new ManagementOperation(new ApiOperation('authority.batch_apply', $grant), ['public_ids'], 'password', previewRequired: true, bulk: true), 'authority_bulk', destructive: true, proofAction: $grant);
        }

        return [new ManagementContribution('user_authority', 'runa.auth.users.profile', new ManagementSection('authority', ['pl' => 'Superadministrator', 'en' => 'Super administrator'], $grant, $grant, 'authority', [
            new ManagementFieldAccess(new ManagementField('grant', 'boolean', ['pl' => 'Superadministrator', 'en' => 'Super administrator'], ['pl' => 'Chronione członkostwo systemowe.', 'en' => 'Protected system membership.'], writable: true), $grant, $grant),
            new ManagementFieldAccess(new ManagementField('review_summary', 'text', ['pl' => 'Skutki zmiany', 'en' => 'Change impact'], ['pl' => 'Przegląd chronionego członkostwa.', 'en' => 'Protected membership review.']), $grant),
        ], [AdministrationPages::command('change', 'Change protected authority', 'authority.apply', ['grant'], 'authority', 'authority.manage', true)], required: false)),
            new ManagementContribution('user_authority_bulk', 'runa.auth.users.profile', new ManagementSection('bulk_authority', ['pl' => 'Superadministrator', 'en' => 'Super administrator'], $grant, $grant, 'authority_bulk', [
                new ManagementFieldAccess(new ManagementField('public_ids', 'uuid', ['pl' => 'Wybrane konta', 'en' => 'Selected accounts'], ['pl' => 'Do 100 jawnie wybranych kont.', 'en' => 'Up to 100 explicitly selected accounts.'], writable: true, required: true, multiple: true), 'runa.permissions.users.select', $grant),
                new ManagementFieldAccess(new ManagementField('summary', 'text', ['pl' => 'Skutki zmiany', 'en' => 'Change impact'], ['pl' => 'Pełny przegląd wybranej operacji.', 'en' => 'Complete review of the selected operation.']), $grant),
            ], $commands, required: false, component: 'runa.permissions.admin_bulk_authority'))];
    }

    public function bindings(): array
    {
        return [new ManagementBinding('authority', AuthorityEditor::class, [new ManagementApiTarget('runa.permissions', PermissionsAuthority::class, '1.0.0', ['previewAuthority', 'applyAuthority'])]),
            new ManagementBinding('authority_bulk', AuthorityBulkEditor::class, [new ManagementApiTarget('runa.permissions', PermissionsAuthorityBatch::class, '1.0.0', ['previewBatch', 'applyBatch']), new ManagementApiTarget('runa.permissions', PermissionsSelections::class, '1.0.0', ['users'])])];
    }
}
