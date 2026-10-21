<?php

$disks = [];
foreach (['application', 'runa'] as $owner) {
    foreach (['private', 'public'] as $visibility) {
        $disks[$owner.'_'.$visibility] = [
            'driver' => 'local',
            'root' => storage_path('app/'.$owner.'/'.$visibility),
            'serve' => false,
            'throw' => true,
        ];
    }
}

return [
    'default' => 'local',
    'disks' => ['local' => $disks['application_private'], ...$disks],
    'links' => [],
];
