<?php

use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementRecord;

it('structured management fields retain bounded native payloads and reject incompatible declaration controls', function (): void {
    $names = ['pl' => 'Dane', 'en' => 'Data'];
    $field = new ManagementField('data', 'json', $names, $names, writable: true);
    expect($field->type)->toBe('json');
    $values = ['data' => ['items' => [['name' => 'Native', 'enabled' => true]], 'optional' => null]];
    expect((new ManagementRecord(null, '0', $values))->values)->toBe($values);
    foreach ([['filterable' => true], ['sortable' => true], ['sensitive' => true], ['multiple' => true],
        ['maxLength' => 10], ['choices' => ['choice']], ['referenceModule' => 'app.reference', 'referenceOperation' => 'read']] as $controls) {
        expect(fn () => new ManagementField('data', 'json', $names, $names, ...$controls))->toThrow(InvalidArgumentException::class);
    }
    expect(fn () => new ManagementRecord(null, '0', ['data' => (object) ['label' => 'Object']]))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ManagementRecord(null, '0', ['data' => ['label' => str_repeat('a', 65536)]]))->toThrow(InvalidArgumentException::class);
    $deep = ['data' => null];
    for ($i = 0; $i < 9; $i++) {
        $deep = ['data' => $deep];
    }
    expect(fn () => new ManagementRecord(null, '0', $deep))->toThrow(InvalidArgumentException::class);
});
