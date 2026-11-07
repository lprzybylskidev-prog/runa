<?php

namespace App\Modules\Reference\Management;

use App\Modules\Reference\Contracts\ReferenceTools;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class ReferenceHistoryReader implements ManagementResourceReader
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
        $result = $this->api()->history(before: $input->cursor);
        $items = array_map(static fn (array $row) => new ManagementRecord($row['public_id'], hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)), array_intersect_key($row, ['action' => true, 'outcome' => true, 'occurred_at' => true])), $result['items']);

        $page = array_slice($items, 0, $input->limit);
        $next = count($page) < count($items) ? $page[array_key_last($page)]->publicId : $result['next'];

        return new ManagementPageResult($page, $next);
    }

    private function api(): ReferenceTools
    {
        return $this->apis->resolve('app.reference', ReferenceTools::class, '1.0.0');
    }
}
