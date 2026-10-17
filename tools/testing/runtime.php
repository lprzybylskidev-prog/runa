<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$root = sys_get_temp_dir().'/runa-health-'.bin2hex(random_bytes(6));
$app->useStoragePath($root);
$heartbeat = $app->make(App\Support\RuntimeHeartbeat::class);
try {
    if ($heartbeat->isFresh('queue', 120)) {
        throw new RuntimeException('Missing heartbeat was accepted.');
    }
    $heartbeat->record('queue');
    if (! $heartbeat->isFresh('queue', 120)) {
        throw new RuntimeException('Fresh heartbeat was rejected.');
    }
    foreach ([['instance' => gethostname(), 'recorded_at' => time() - 180],
        ['instance' => 'another-container', 'recorded_at' => time()],
        ['instance' => gethostname(), 'recorded_at' => time() + 60]] as $data) {
        file_put_contents($root.'/framework/health/queue.json', json_encode($data, JSON_THROW_ON_ERROR));
        if ($heartbeat->isFresh('queue', 120)) {
            throw new RuntimeException('Invalid heartbeat was accepted.');
        }
    }
    $validator = $app->make(App\Support\ConfigurationValidator::class);
    config(['mail.mailers.smtp.port' => 70000]);
    if (! in_array('mail.mailers.smtp.port must be a valid TCP port.', $validator->errors(), true)) {
        throw new RuntimeException('Invalid SMTP port was accepted.');
    }
    config(['mail.mailers.smtp.port' => 1025, 'mail.mailers.smtp.username' => 'incomplete-user']);
    if (! in_array('SMTP username and password must be configured together.', $validator->errors(), true)) {
        throw new RuntimeException('Incomplete SMTP credentials were accepted.');
    }
    config(['app.env' => 'production', 'app.debug' => false, 'app.url' => 'https://runa.example.test',
        'runa.knowledge_enabled' => false, 'mail.mailers.smtp.username' => null,
        'mail.mailers.smtp.password' => null, 'mail.mailers.smtp.require_tls' => false]);
    if (! in_array('Production SMTP requires TLS.', $validator->errors(), true)) {
        throw new RuntimeException('Unencrypted production SMTP was accepted.');
    }
    if (! in_array('Production requires a configured outbound SMTP host, not Mailpit or localhost.', $validator->errors(), true)) {
        throw new RuntimeException('Production Mailpit was accepted.');
    }
    fwrite(STDOUT, "PASS: missing/stale/foreign/future heartbeats and invalid SMTP configuration are rejected.\n");
} finally {
    $app->make(Illuminate\Filesystem\Filesystem::class)->deleteDirectory($root);
}
