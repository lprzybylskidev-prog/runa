<?php

namespace App\Modules\Reference\Management;

use App\Modules\Reference\Contracts\ReferenceTools;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class ReferenceSettingsReader implements ManagementResourceReader
{
    public function __construct(private ModuleApis $apis) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        if ($input->publicId !== null || $input->cursor !== null || $input->filters !== [] || $input->sort !== []) {
            throw new OperationRejected('reference_query_invalid', 'app.reference', 'management.read', 422);
        }
        $result = $this->apis->resolve('app.reference', ReferenceTools::class, '1.0.0')->settings();
        $values = ['label' => $result['settings']['label']['value']];
        if (isset($result['settings']['credential'])) {
            $values['credential'] = ['set' => $result['settings']['credential']['set']];
        }

        return new ManagementRecord(null, $result['revision'].':'.$result['review_id'], $values);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        return new ManagementPageResult([$this->read($input)], null, 1);
    }
}
