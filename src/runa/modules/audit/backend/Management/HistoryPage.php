<?php

namespace Runa\Modules\Audit\Management;

use Runa\Contracts\Modules\ManagementApiTarget;
use Runa\Contracts\Modules\ManagementBinding;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementFieldAccess;
use Runa\Contracts\Modules\ManagementPage;
use Runa\Contracts\Modules\ManagementPageProvider;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Modules\Audit\Contracts\AuditReads;

final class HistoryPage implements ManagementPageProvider
{
    /** @var array<string, string> */
    private const FIELD_NAMES = [
        'occurred_at' => 'Czas zdarzenia',
        'module' => 'Moduł',
        'action' => 'Akcja',
        'actor_public_id' => 'Publiczny UUID wykonawcy',
        'actor_kind' => 'Typ wykonawcy',
        'actor_label' => 'Nazwa wykonawcy',
        'outcome' => 'Wynik',
        'correlation_id' => 'Identyfikator powiązania',
        'entity_module' => 'Moduł obiektu',
        'entity_type' => 'Typ obiektu',
        'entity_public_id' => 'Publiczny UUID obiektu',
        'from' => 'Od',
        'until' => 'Do',
        'entities' => 'Obiekty',
        'changes' => 'Zmiany',
        'context' => 'Kontekst',
        'execution' => 'Wykonanie',
        'payload_part' => 'Część danych',
        'payload_next' => 'Następna część danych',
    ];

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
        $summary = [];
        $choices = [
            'actor_kind' => ['human', 'anonymous', 'system', 'scheduler', 'queue', 'cli', 'machine'],
            'outcome' => ['success', 'attempt', 'refused', 'failure'],
        ];
        foreach (['occurred_at', 'module', 'action', 'actor_public_id', 'actor_kind', 'actor_label', 'outcome', 'correlation_id', 'entity_module', 'entity_type', 'entity_public_id', 'from', 'until'] as $key) {
            $text = ['pl' => self::FIELD_NAMES[$key], 'en' => ucwords(str_replace('_', ' ', $key))];
            $summary[] = new ManagementFieldAccess(new ManagementField($key, $key === 'occurred_at' ? 'datetime' : (isset($choices[$key]) ? 'dictionary' : 'text'), $text, $text, filterable: ! in_array($key, ['occurred_at', 'actor_label'], true), choices: $choices[$key] ?? []), 'runa.audit.read');
        }
        $detail = $summary;
        foreach (['entities', 'changes', 'context', 'execution', 'payload_part', 'payload_next'] as $key) {
            $text = ['pl' => self::FIELD_NAMES[$key], 'en' => ucfirst($key)];
            $detail[] = new ManagementFieldAccess(new ManagementField($key, 'text', $text, $text), 'runa.audit.read');
        }

        return [new ManagementPage('history', 'Audit history', 'runa.audit.read', [
            new ManagementSection('catalogue', 'Events', 'runa.audit.read', null, 'history', $summary, view: 'table'),
            new ManagementSection('detail', 'Event detail', 'runa.audit.read', null, 'history', $detail, component: 'runa.audit.admin_detail'),
        ], component: 'runa.audit.admin_history')];
    }

    public function bindings(): array
    {
        return [new ManagementBinding('history', HistoryReader::class, [new ManagementApiTarget('runa.audit', AuditReads::class, '1.0.0', ['page', 'detail'])])];
    }
}
