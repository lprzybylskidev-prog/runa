<?php

namespace App\Modules\Reference\Management;

use App\Modules\Reference\Contracts\Ping;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementWidgetReader;
use Runa\Contracts\Modules\ManagementWidgetResult;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

/** Read-only presentation; the guarded owning API supplies the value. */
final readonly class ReferenceReader implements ManagementResourceReader, ManagementWidgetReader
{
    public function __construct(private ModuleApis $apis) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        if ($input->publicId !== null || $input->cursor !== null || $input->filters !== [] || $input->sort !== []) {
            throw new OperationRejected('reference_query_invalid', 'app.reference', 'reference.read', 422);
        }

        $message = $this->apis->resolve('app.reference', Ping::class, '1.0.0')->read()->message;

        return new ManagementRecord(null, hash('sha256', $message), ['message' => $message]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        return new ManagementPageResult([$this->read($input)], null, 1);
    }

    public function widget(ManagementRead $input): ManagementWidgetResult
    {
        return new ManagementWidgetResult($this->read($input)->values);
    }
}
