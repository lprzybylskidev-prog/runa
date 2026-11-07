<?php

namespace App\Modules\Reference\Definitions;

use App\Modules\Reference\Contracts\Ping;
use App\Modules\Reference\Management\ReferenceReader;
use Runa\Contracts\Modules\ManagementAction;
use Runa\Contracts\Modules\ManagementApiTarget;
use Runa\Contracts\Modules\ManagementBinding;
use Runa\Contracts\Modules\ManagementComponent;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementFieldAccess;
use Runa\Contracts\Modules\ManagementOptionsRead;
use Runa\Contracts\Modules\ManagementPage;
use Runa\Contracts\Modules\ManagementPermission;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementReadCall;
use Runa\Contracts\Modules\ManagementReadPlanProvider;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Contracts\Modules\ManagementWidget;

final class ReferenceManagement implements ManagementReadPlanProvider
{
    public function permissions(): array
    {
        return [...ReferencePermissions::permissions(), new ManagementPermission('reference.inspect', ['pl' => 'Inspekcja przykładu', 'en' => 'Inspect reference'], ['pl' => 'Odczyt diagnostyki przykładu.', 'en' => 'Read reference diagnostics.'])];
    }

    public function actions(): array
    {
        return [new ManagementAction('inspect', 'reference.inspect', ['pl' => 'Sprawdź przykład', 'en' => 'Inspect reference'], ['pl' => 'Odczyt odpowiedzi publicznego API bez zmiany danych.', 'en' => 'Read the public API response without changing data.'])];
    }

    public function components(): array
    {
        return [new ManagementComponent('reference.section', 'app.reference.inspect_section', 'reference.inspect')];
    }

    public function pages(): array
    {
        $permission = 'app.reference.reference.inspect';

        return [new ManagementPage('inspect', ['pl' => 'Moduł referencyjny', 'en' => 'Reference module'], $permission, [
            new ManagementSection('response', ['pl' => 'Odpowiedź API', 'en' => 'API response'], $permission, null, 'inspect', [
                new ManagementFieldAccess(new ManagementField('message', 'text', ['pl' => 'Etykieta', 'en' => 'Label'],
                    ['pl' => 'Bieżąca wartość z publicznego API modułu.', 'en' => 'Current value from the module public API.']), $permission),
            ], component: 'app.reference.inspect_section'),
        ], default: true)];
    }

    public function bindings(): array
    {
        return [new ManagementBinding('inspect', ReferenceReader::class,
            [new ManagementApiTarget('app.reference', Ping::class, '1.0.0', ['read'])])];
    }

    public function contributions(): array
    {
        return [];
    }

    public function readPlan(string $binding, string $capability, ManagementRead|ManagementOptionsRead $input): array
    {
        return $binding === 'inspect' && in_array($capability, ['read', 'page', 'widget'], true)
            ? [new ManagementReadCall('app.reference', Ping::class, '1.0.0', 'read')] : [];
    }

    public function widgets(): array
    {
        return [new ManagementWidget('response', ['pl' => 'Moduł referencyjny', 'en' => 'Reference module'],
            'app.reference.reference.inspect', 'inspect', component: 'app.reference.response_widget')];
    }
}
