<?php

declare(strict_types=1);
use Illuminate\Support\Str;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\SettingsObserver;
use Runa\Support\Modules\Api\DenyProtectedOperations;

require '/workspace/src/vendor/autoload.php';

$expect = static function (bool $condition, string $message): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};

$expect(PHP_VERSION === '8.4.27', 'Unexpected production PHP version.');
$expect(posix_geteuid() === 1000, 'Production PHP must run as the application user.');
$expect(date_default_timezone_get() === 'UTC', 'Production PHP must use UTC.');
foreach (['pdo_pgsql', 'mbstring', 'intl', 'bcmath', 'Zend OPcache', 'pcntl', 'zip', 'curl', 'xml', 'dom', 'SimpleXML'] as $extension) {
    $expect(extension_loaded($extension), 'Missing runtime extension: '.$extension);
}
$expect(! extension_loaded('xdebug'), 'Development extension in runtime.');
$expect(in_array('pgsql', PDO::getAvailableDrivers(), true), 'Missing PDO PostgreSQL driver.');
foreach ([OperationAuthorizer::class, SettingsObserver::class] as $contract) {
    $expect(interface_exists($contract), 'Missing public module runtime contract: '.$contract);
}
$expect(class_exists(DenyProtectedOperations::class), 'Module authorization adapter cannot load.');
$expect(mb_strtolower('ZAŻÓŁĆ GĘŚLĄ JAŹŃ', 'UTF-8') === 'zażółć gęślą jaźń', 'Polish multibyte conversion failed.');
$text = 'Zażółć gęślą jaźń';
$encoded = iconv('UTF-8', 'UTF-16LE', $text);
$expect(is_string($encoded) && iconv('UTF-16LE', 'UTF-8', $encoded) === $text, 'Lossless iconv Unicode round trip failed.');
$expectedTransliteration = match (ICONV_IMPL) {
    'libiconv' => "Zaz'ol'c ge'sla ja'z'n",
    'glibc' => 'Zazolc gesla jazn',
    default => throw new RuntimeException('Unreviewed iconv implementation: '.ICONV_IMPL),
};
$expect(iconv('UTF-8', 'ASCII//TRANSLIT', $text) === $expectedTransliteration, 'Implementation-specific iconv transliteration changed.');
$expect(Str::ascii('Zażółć gęślą jaźń', 'pl') === 'Zazolc gesla jazn', 'Laravel Polish transliteration failed.');
$collator = new Collator('pl_PL');
$alphabet = ['ż', 'ź', 'z', 'ś', 's', 'ó', 'o', 'ń', 'n', 'ł', 'l', 'ę', 'e', 'ć', 'c', 'ą', 'a'];
$expect($collator->sort($alphabet), 'Polish ICU sorting failed.');
$expect($alphabet === ['a', 'ą', 'c', 'ć', 'e', 'ę', 'l', 'ł', 'n', 'ń', 'o', 'ó', 's', 'ś', 'z', 'ź', 'ż'], 'Polish ICU data/order is unavailable.');
$expect(str_starts_with($collator->getLocale(Locale::VALID_LOCALE), 'pl'), 'ICU fell back from Polish.');
$expect(bcadd('0.10', '0.20', 2) === '0.30', 'Decimal arithmetic failed.');
$xml = new DOMDocument;
$expect($xml->loadXML('<runa>Zażółć</runa>'), 'XML parse failed.');
$expect($xml->documentElement?->textContent === 'Zażółć', 'XML Unicode decoding failed.');
$curl = curl_version();
$expect(in_array('https', $curl['protocols'], true) && $curl['ssl_version'] !== '', 'HTTPS client support is unavailable.');
$file = tempnam('/tmp', 'runa-zip-');
$expect(is_string($file), 'Writable temporary storage is unavailable.');
try {
    $zip = new ZipArchive;
    $expect($zip->open($file, ZipArchive::OVERWRITE) === true, 'ZIP creation failed.');
    $expect($zip->addFromString('polish.txt', 'Zażółć'), 'ZIP write failed.');
    $expect($zip->close(), 'ZIP close failed.');
    $expect($zip->open($file) === true, 'ZIP reopening failed.');
    $expect($zip->getFromName('polish.txt') === 'Zażółć', 'ZIP round trip failed.');
    $zip->close();
} finally {
    unlink($file);
}
$pid = pcntl_fork();
$expect($pid !== -1, 'Process fork failed.');
if ($pid === 0) {
    exit(0);
}
$expect(pcntl_waitpid($pid, $status) === $pid && pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0, 'Process wait failed.');
$process = proc_open([PHP_BINARY, '-r', 'exit(42);'], [], $pipes);
$expect(is_resource($process) && proc_close($process) === 42, 'Application child-process execution failed.');
foreach (['.git', '.env', '.agents', '.devcontainer', 'docs', 'tools', 'bin', 'tests', 'node_modules', 'src/tests', 'src/runa/tests', 'src/app/modules/reference', 'src/app/modules/module_probe', 'src/app/tests', 'src/runa/tools', 'src/runa/management', 'src/node_modules', 'src/vendor/laravel/boost', 'src/vendor/pestphp', 'src/vendor/phpstan'] as $path) {
    $expect(! file_exists('/workspace/'.$path), 'Development material in runtime: '.$path);
}
$installed = json_decode(file_get_contents('/workspace/src/vendor/composer/installed.json'), true, 512, JSON_THROW_ON_ERROR);
$expect($installed['dev'] === false, 'Development dependencies included.');
$expect(is_file('/workspace/src/public/build/index.html'), 'Missing SPA build.');
fwrite(STDOUT, 'PASS: PHP version, extensions, Unicode/ICU/iconv, decimal/XML/ZIP/HTTPS/process capabilities, runtime user and isolation. ICU '.INTL_ICU_VERSION."\n");
