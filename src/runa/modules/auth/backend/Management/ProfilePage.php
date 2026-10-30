<?php

namespace Runa\Modules\Auth\Management;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementApiTarget;
use Runa\Contracts\Modules\ManagementBinding;
use Runa\Contracts\Modules\ManagementCommandDefinition;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementFieldAccess;
use Runa\Contracts\Modules\ManagementOperation;
use Runa\Contracts\Modules\ManagementPage;
use Runa\Contracts\Modules\ManagementPageProvider;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Auth\Contracts\AuthProfiles;

/** Initial native record composition. The directory and lifecycle pages are separate capabilities. */
final class ProfilePage implements ManagementPageProvider
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

    public function contributions(): array
    {
        return [];
    }

    public function widgets(): array
    {
        return [];
    }

    public function pages(): array
    {
        $fields = [];
        foreach (['name' => 'Display name', 'first_name' => 'First name', 'surname' => 'Surname', 'login' => 'Login'] as $key => $label) {
            $text = ['pl' => $label, 'en' => $label];
            $fields[] = new ManagementFieldAccess(new ManagementField($key, 'text', $text, $text, writable: true, maxLength: $key === 'login' ? 64 : 160),
                'runa.auth.identities.read', 'runa.auth.profiles.manage');
        }

        return [new ManagementPage('users', 'Users', 'runa.auth.identities.read', [
            new ManagementSection('profile', 'Profile', 'runa.auth.identities.read', 'runa.auth.profiles.manage', 'profile', $fields, [
                new ManagementCommandDefinition('save', 'Save profile', new ManagementOperation(new ApiOperation('profile.edit', 'runa.auth.profiles.manage'),
                    ['name', 'first_name', 'surname', 'login']), 'profile', 'atomic', ProfileEditor::class, intent: 'save'),
            ]),
        ])];
    }

    public function bindings(): array
    {
        return [new ManagementBinding('profile', ProfileEditor::class, [
            new ManagementApiTarget('runa.auth', AuthIdentities::class, '1.0.0', ['identity']),
            new ManagementApiTarget('runa.auth', AuthProfiles::class, '2.0.0', ['edit']),
        ], ProfileEditor::class)];
    }
}
