<?php

namespace Runa\Modules\Permissions\Management;

use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Permissions\Contracts\Permissions;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class EffectivePermissions implements ManagementResourceReader
{
    public function __construct(private ModuleApis $apis, private PrincipalScope $principals) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        if ($input->filters !== [] || $input->sort !== []) {
            throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'permissions.explain', 422);
        }
        $id = $input->publicId ?? $this->principals->current()?->publicId ?? throw new OperationRejected('authentication_required', 'runa.permissions', 'permissions.explain', 401);
        $page = $this->apis->resolve('runa.permissions', Permissions::class, '1.0.0')->explain($id, $input->cursor);
        $items = array_slice($page->permissions, 0, $input->limit);
        $next = count($items) < count($page->permissions) ? $items[array_key_last($items)]['code'] : $page->next;

        return new ManagementRecord($id, hash('sha256', serialize([$page->eligible, $page->superAdmin, $items, $next])), [
            'entries' => json_encode($items, JSON_THROW_ON_ERROR), 'next' => $next,
        ]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.permissions', 'permissions.explain', 404);
    }
}
