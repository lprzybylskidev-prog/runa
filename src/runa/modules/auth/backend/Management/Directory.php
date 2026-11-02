<?php

namespace Runa\Modules\Auth\Management;

use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Auth\Contracts\UserDirectoryQuery;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class Directory implements ManagementResourceReader
{
    public function __construct(private ModuleApis $apis) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'administration.users', 404);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        if ($input->sort !== [] || array_diff(array_keys($input->filters), ['display_name', 'state']) !== []) {
            throw new OperationRejected('directory_query_invalid', 'runa.auth', 'administration.users', 422);
        }
        try {
            $query = new UserDirectoryQuery($input->cursor, $input->limit, $input->filters['display_name'] ?? null, $input->filters['state'] ?? 'current');
        } catch (\InvalidArgumentException|\TypeError) {
            throw new OperationRejected('directory_query_invalid', 'runa.auth', 'administration.users', 422);
        }
        $result = $this->apis->resolve('runa.auth', AuthAdministration::class, '1.0.0')->users($query);

        return new ManagementPageResult(array_map(fn ($user) => new ManagementRecord($user->publicId, (string) $user->version,
            ['display_name' => $user->displayName, 'state' => $user->state, ...$user->details]), $result->items), $result->next, $result->total);
    }
}
