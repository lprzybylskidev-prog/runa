<?php

namespace App\AdminContractFixture;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementEntity;
use Runa\Contracts\Modules\ManagementEntityProvider;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementOperation;
use Runa\Contracts\Modules\ManagementPermission;

/** Existing application consumer; never registers a phantom Admin module. */
final class LegacyProvider implements ManagementEntityProvider
{
    public function permissions(): array
    {
        return [new ManagementPermission('records.manage', ['pl' => 'Zarządzanie', 'en' => 'Manage'], ['pl' => 'Zarządzanie', 'en' => 'Manage'])];
    }

    public function actions(): array
    {
        return [];
    }

    public function components(): array
    {
        return [];
    }

    public function entities(): array
    {
        return [new ManagementEntity('record', 'records.manage', ['pl' => 'Rekord', 'en' => 'Record'], ['pl' => 'Rekord', 'en' => 'Record'],
            [new ManagementField('title', 'text', ['pl' => 'Tytuł', 'en' => 'Title'], ['pl' => 'Tytuł', 'en' => 'Title'], writable: true, required: true, maxLength: 160)],
            [new ManagementOperation(new ApiOperation('save', 'records.manage'), ['title'])])];
    }
}
