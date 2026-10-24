<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\ManagementEntity;
use Runa\Contracts\Modules\ManagementEntityProvider;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementPermission;

final class AuditPermissions implements ManagementEntityProvider
{
    public function permissions(): array
    {
        return [
            new ManagementPermission('read', ['pl' => 'Odczyt historii audytu', 'en' => 'Read audit history'], ['pl' => 'Odczyt historii audytu.', 'en' => 'Read audit history.']),
        ];
    }

    public function entities(): array
    {
        $fields = [];
        foreach (['public_id' => 'uuid', 'occurred_at' => 'datetime', 'module' => 'text', 'action' => 'text',
            'actor_public_id' => 'uuid', 'actor_kind' => 'dictionary', 'outcome' => 'dictionary', 'correlation_id' => 'text'] as $key => $type) {
            $fields[] = new ManagementField($key, $type, ['pl' => $key, 'en' => $key],
                ['pl' => 'Bezpieczne podsumowanie zdarzenia.', 'en' => 'Safe immutable event summary; bounded append-order reads.'],
                filterable: $key !== 'public_id');
        }

        return [new ManagementEntity('event', 'read', ['pl' => 'Historia', 'en' => 'History'],
            ['pl' => 'Tylko odczyt; zmiany i kontekst wyłącznie w szczegółach.', 'en' => 'Read-only; changes/context only in authorized detail. Actor UUIDs resolve separately through Auth when available.'], $fields, [])];
    }

    public function actions(): array
    {
        return [];
    }

    public function components(): array
    {
        return [];
    }
}
