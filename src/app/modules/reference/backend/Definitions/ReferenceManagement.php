<?php

namespace App\Modules\Reference\Definitions;

use App\Modules\Reference\Contracts\Ping;
use App\Modules\Reference\Contracts\ReferenceTools;
use App\Modules\Reference\Management\ReferenceHistoryReader;
use App\Modules\Reference\Management\ReferenceReader;
use App\Modules\Reference\Management\ReferenceSamplesReader;
use App\Modules\Reference\Management\ReferenceSettingsReader;
use App\Modules\Reference\Management\ReferenceWorkReader;
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
        return [new ManagementComponent('reference.section', 'app.reference.inspect_section', 'reference.inspect'),
            ...array_map(static fn (string $key) => new ManagementComponent('reference.'.$key, 'app.reference.tools_page', $key.'.read'), ['samples', 'settings', 'history', 'work'])];
    }

    public function pages(): array
    {
        $permission = 'app.reference.reference.inspect';

        return [new ManagementPage('inspect', ['pl' => 'Moduł referencyjny', 'en' => 'Reference module'], $permission, [
            new ManagementSection('response', ['pl' => 'Odpowiedź API', 'en' => 'API response'], $permission, null, 'inspect', [
                new ManagementFieldAccess(new ManagementField('message', 'text', ['pl' => 'Etykieta', 'en' => 'Label'],
                    ['pl' => 'Bieżąca wartość z publicznego API modułu.', 'en' => 'Current value from the module public API.']), $permission),
            ], component: 'app.reference.inspect_section'),
        ], default: true),
            $this->toolsPage('samples', ['pl' => 'Rekordy', 'en' => 'Samples'], [
                new ManagementFieldAccess(new ManagementField('code', 'text', ['pl' => 'Kod', 'en' => 'Code'], ['pl' => 'Kod rekordu.', 'en' => 'Sample code.'], writable: true, maxLength: 40), 'app.reference.samples.code.read', 'app.reference.samples.code.write'),
            ], 'samples.write', 'table'),
            $this->toolsPage('settings', ['pl' => 'Ustawienia i migawki', 'en' => 'Settings and snapshots'], [
                new ManagementFieldAccess(new ManagementField('label', 'text', ['pl' => 'Etykieta', 'en' => 'Label'], ['pl' => 'Opublikowana etykieta.', 'en' => 'Committed label.'], writable: true, maxLength: 40), 'app.reference.settings.read', 'app.reference.settings.write'),
                new ManagementFieldAccess(new ManagementField('credential', 'text', ['pl' => 'Sekret', 'en' => 'Secret'], ['pl' => 'Tylko stan ustawienia sekretu.', 'en' => 'Secret set state only.'], writable: true, sensitive: true, maxLength: 512), 'app.reference.settings.credential.read', 'app.reference.settings.credential.write'),
            ], 'settings.write'),
            $this->toolsPage('history', ['pl' => 'Historia zmian', 'en' => 'Change history'], [
                $this->readField('action', 'Działanie', 'Action', 'history.read'),
                $this->readField('outcome', 'Wynik', 'Outcome', 'history.read'),
                $this->readField('occurred_at', 'Data', 'Date', 'history.read', 'datetime'),
            ], null, 'table'),
            $this->toolsPage('work', ['pl' => 'Zadania', 'en' => 'Work'], [
                $this->readField('status', 'Stan', 'Status', 'work.read'),
                $this->readField('progress', 'Postęp', 'Progress', 'work.read'),
            ], 'work.dispatch', 'table'),
        ];
    }

    public function bindings(): array
    {
        return [new ManagementBinding('inspect', ReferenceReader::class,
            [new ManagementApiTarget('app.reference', Ping::class, '1.0.0', ['read'])]),
            ...array_map(static fn (string $key, string $handler) => new ManagementBinding($key, $handler,
                [new ManagementApiTarget('app.reference', ReferenceTools::class, '1.0.0', [$key])]),
                ['samples', 'settings', 'history', 'work'], [ReferenceSamplesReader::class, ReferenceSettingsReader::class, ReferenceHistoryReader::class, ReferenceWorkReader::class]),
        ];
    }

    public function contributions(): array
    {
        return [];
    }

    public function readPlan(string $binding, string $capability, ManagementRead|ManagementOptionsRead $input): array
    {
        if (in_array($binding, ['samples', 'settings', 'history', 'work'], true) && in_array($capability, ['read', 'page'], true)) {
            return [new ManagementReadCall('app.reference', ReferenceTools::class, '1.0.0', $binding)];
        }

        return $binding === 'inspect' && in_array($capability, ['read', 'page', 'widget'], true)
            ? [new ManagementReadCall('app.reference', Ping::class, '1.0.0', 'read')] : [];
    }

    /** @param array{pl:string,en:string} $title
     * @param list<ManagementFieldAccess> $fields */
    private function toolsPage(string $key, array $title, array $fields, ?string $write, string $view = 'record'): ManagementPage
    {
        $read = 'app.reference.'.$key.'.read';

        return new ManagementPage($key, $title, $read, [new ManagementSection($key, $title, $read,
            $write === null ? null : 'app.reference.'.$write, $key, $fields, view: $view)], component: 'app.reference.tools_page');
    }

    private function readField(string $key, string $pl, string $en, string $permission, string $type = 'text'): ManagementFieldAccess
    {
        return new ManagementFieldAccess(new ManagementField($key, $type, ['pl' => $pl, 'en' => $en],
            ['pl' => $pl.'.', 'en' => $en.'.']), 'app.reference.'.$permission);
    }

    public function widgets(): array
    {
        return [new ManagementWidget('response', ['pl' => 'Moduł referencyjny', 'en' => 'Reference module'],
            'app.reference.reference.inspect', 'inspect', component: 'app.reference.response_widget')];
    }
}
