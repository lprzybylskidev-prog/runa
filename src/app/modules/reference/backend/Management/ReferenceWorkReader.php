<?php

namespace App\Modules\Reference\Management;

use App\Modules\Reference\Contracts\ReferenceTools;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class ReferenceWorkReader implements ManagementResourceReader
{
    public function __construct(private ModuleApis $apis) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        throw new OperationRejected('reference_query_invalid', 'app.reference', 'management.read', 422);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        if ($input->publicId !== null || $input->filters !== [] || $input->sort !== []) {
            throw new OperationRejected('reference_query_invalid', 'app.reference', 'management.page', 422);
        }
        $result = $this->api()->work();
        $items = array_map(static fn (array $row) => new ManagementRecord($row['work_id'], hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)), array_intersect_key($row, ['status' => true, 'progress' => true])), $result['items']);

        $offset = 0;
        if ($input->cursor !== null) {
            $position = array_search($input->cursor, array_map(static fn (ManagementRecord $row) => $row->publicId, $items), true);
            if ($position === false) {
                throw new OperationRejected('reference_query_invalid', 'app.reference', 'management.page', 422);
            }
            $offset = $position + 1;
        }
        $page = array_slice($items, $offset, $input->limit);
        $next = $offset + count($page) < count($items) ? ($page === [] ? null : $page[array_key_last($page)]->publicId) : null;

        return new ManagementPageResult($page, $next, count($items));
    }

    private function api(): ReferenceTools
    {
        return $this->apis->resolve('app.reference', ReferenceTools::class, '1.0.0');
    }
}
