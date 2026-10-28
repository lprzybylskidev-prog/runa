"""Focused audience checks without booting application code or starting services."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[4]


def php(code: str, *arguments: str) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        ["php", "-r", "require $argv[1].'/src/vendor/autoload.php';" + code, str(ROOT), *arguments],
        cwd=ROOT,
        text=True,
        capture_output=True,
        check=False,
    )


def test_php_aliases_reserved_callers_and_public_support_remain_distinct() -> None:
    code = r"""
$boundary = new Runa\ModuleFramework\Development\ApiBoundary;
$cases = [
 ['<?php use Runa\Modules\Auth\Contracts\AuthProfiles as Profiles;', [], ''],
 ['<?php use Runa\Contracts\Modules as M; function x(M\WorkHandler $h): void {}', [], ''],
 ['<?php use Runa\Modules\Auth\Contracts as C; function x(C\AuthorizationIdentity $a): void {}', ['reserved_api_import'], ''],
 ['<?php use Runa\Contracts\Modules\{WorkHandler, AuthorityGuard as Guard};', ['reserved_api_import'], ''],
 ['<?php new \Runa\ModuleFramework\ModuleRegistry;', ['internal_platform_import'], ''],
 ['<?php use Runa\Support\Modules\Work\WorkContext; use Runa\Support\Modules\Data\ModuleModel;', [], ''],
 ['<?php use Runa\Modules\Auth\Contracts\Unknown;', ['foreign_module_import'], ''],
 ['<?php use Runa\Modules\Auth\Contracts\AuthorizationIdentity;', [], 'src/runa/Integration/Core/AuthAuthorizationIdentity.php'],
 ['<?php use Runa\Modules\Auth\Contracts\AuthorizationIdentity;', ['reserved_api_import'], 'src/app/AuthAuthorizationIdentity.php'],
 ['<?php // use Runa\Modules\Audit\Contracts\AuditWriter;' , [], ''],
 ['<?php use App\Modules\Foreign\Models\Secret;', ['foreign_module_import'], ''],
];
foreach ($cases as [$source, $expected, $path]) {
 if ($boundary->php($source, 'App\Modules\Own\\', $path) !== $expected) { fwrite(STDERR, $source); exit(10); }
}
$parser = new Runa\Development\ApiSourceCheck;
$source = '<?php namespace Runa\Contracts\Modules; interface Unknown { public function x(AuthorityGuard $guard): void; }';
if (! in_array('Runa\Contracts\Modules\AuthorityGuard', $parser->references($source), true)) exit(11);
"""
    result = php(code)
    assert result.returncode == 0, result.stderr


def test_native_public_signature_closure_and_ordinary_application_scan(tmp_path: Path) -> None:
    code = r"""
$check = new Runa\Development\ApiSourceCheck;
$valid = '<?php namespace Runa\Contracts\Modules; interface WorkHandler { public function run(\Runa\Support\Modules\Work\WorkContext $context): WorkStatus; }';
$invalid = '<?php namespace Runa\Contracts\Modules; use Runa\ModuleFramework as Engine; interface WorkHandler { public function run(Engine\ModuleRegistry|\Runa\Support\Modules\Work\WorkContext $context): void; }';
if ($check->signatures($valid, 'public.php') !== []) exit(20);
if ($check->signatures($invalid, 'public.php') !== ['public.php:public_signature_internal_type:Runa\ModuleFramework\ModuleRegistry']) exit(21);
$doc = '<?php namespace Runa\Contracts\Modules; use Runa\ModuleFramework\ModuleRegistry as Registry; interface WorkHandler { /** @return list<Registry> */ public function run(): array; }';
if ($check->signatures($doc, 'doc.php') !== ['doc.php:public_signature_internal_type:Runa\ModuleFramework\ModuleRegistry']) exit(23);
$promoted = '<?php namespace Runa\Support\Modules; final class ModuleRecord { public function __construct(public \Runa\ModuleFramework\ModuleRegistry $registry) {} }';
if ($check->signatures($promoted, 'value.php') !== ['value.php:public_signature_internal_type:Runa\ModuleFramework\ModuleRegistry']) exit(24);
$errors = $check->check($argv[2]);
if ($errors !== ['src/app/Service.php:reserved_api_import']) { fwrite(STDERR, json_encode($errors)); exit(22); }
"""
    application = tmp_path / "src/app"
    application.mkdir(parents=True)
    (application / "Service.php").write_text(
        r"<?php namespace App; use Runa\Modules\Audit\Contracts\AuditWriter as Writer; "
        r"final class Service { public function run(Writer $writer): void {} }"
    )
    result = php(code, str(tmp_path))
    assert result.returncode == 0, result.stderr


def test_frontend_rule_uses_ast_and_resolves_alias_relative_and_type_imports() -> None:
    code = r"""
import path from 'node:path';
import { ESLint } from './src/node_modules/eslint/lib/api.js';
const filename = path.resolve('src/app/resources/js/Boundary.ts');
const eslint = new ESLint();
const config = await eslint.calculateConfigForFile(filename);
if (!config.rules?.['runa/public-imports']) throw new Error('Audience rule missing from resolved source config');
for (const [source, expected] of [
 ["import type { AuthEntryController } from '@/runa/auth'; export type X = AuthEntryController;", 0],
 ["export { http } from '@runa/lib/http';", 1],
 ["export * from '../../../runa/modules/auth/frontend/contracts/controllers';", 1],
 ["void import('@runa/components/AppButton.vue');", 1],
 ["export type X = import('@runa/lib/http').HttpOptions;", 1],
 ["import { nextTick } from '@runa/testing/vue'; void nextTick;", 0],
 ["// import X from '@runa/lib/http';\nexport const x = 1;", 0],
]) {
 const [result] = await eslint.lintText(source, { filePath: filename });
 const errors = result.messages.filter(message => message.ruleId === 'runa/public-imports');
 if (errors.length !== expected) throw new Error(JSON.stringify({ source, errors: result.messages }));
}
const [vue] = await eslint.lintText('<script setup lang="ts">import { ref } from "@runa/lib/private"; void ref;</script><template><div /></template>', { filePath: path.resolve('src/app/resources/js/Boundary.vue') });
if (!vue.messages.some(message => message.ruleId === 'runa/public-imports')) throw new Error('Vue import was not inspected');
"""
    result = subprocess.run(
        ["node", "--input-type=module", "-e", code],
        cwd=ROOT,
        text=True,
        capture_output=True,
        check=False,
    )
    assert result.returncode == 0, result.stderr


def test_runtime_audience_data_matches_frozen_public_symbols_and_named_integrations() -> None:
    baseline = json.loads(
        (ROOT / "docs/runa/maintainer/architecture/api-boundary/source-map.json").read_text()
    )
    policy = json.loads(
        (ROOT / "src/runa/ModuleFramework/Development/api-boundary.json").read_text()
    )
    expected = {
        row["target_symbol"]
        for row in baseline["files"]
        if row.get("target_symbol") and row["audience"].startswith("public")
    }
    assert set(policy["public"]) == expected
    for callers in (*policy["reserved"].values(), *policy["internal_callers"].values()):
        assert callers
        assert all(name.startswith("src/runa/") and (ROOT / name).is_file() for name in callers)


def test_production_seed_refusal_does_not_load_excluded_development_support(tmp_path: Path) -> None:
    result = php(
        r"""
$app = new Illuminate\Foundation\Application($argv[2]);
$app->instance('env', 'production');
Illuminate\Container\Container::setInstance($app);
$provider = new Runa\Providers\DevelopmentServiceProvider($app);
$provider->register();
try { (new Runa\Console\Commands\SeedProject('development'))->handle(); exit(31); }
catch (RuntimeException $error) { if ($error->getMessage() !== 'Development seeders require local or testing environment.') exit(32); }
$seed = new class extends Runa\Support\Seeding\ManagedSeeder {
 public static function category(): Runa\Support\Seeding\SeedCategory { return Runa\Support\Seeding\SeedCategory::Development; }
 protected function seed(Runa\Support\Seeding\SeedContext $context): void { throw new LogicException('No production execution'); }
};
try { $seed->run(); exit(33); }
catch (RuntimeException $error) { if ($error->getMessage() !== 'Development seeders require local or testing environment.') exit(34); }
if (class_exists(Runa\Support\Seeding\DevelopmentFixtures::class, false)) exit(35);
""",
        str(tmp_path),
    )
    assert result.returncode == 0, result.stderr


def test_generated_public_consumers_and_module_upgrade_history_without_execution(
    tmp_path: Path,
) -> None:
    # Final acceptance: generate every shape, then inspect with the same customer policy.
    (tmp_path / "src/runa").mkdir(parents=True)
    (tmp_path / "src/runa/platform.json").write_text('{"version":"0.6.0"}')
    result = php(
        r"""
$root = $argv[2];
$generator = new Runa\Development\Modules\ModuleGenerator($root, true);
$generator->write($generator->plan('app.public_consumer'));
foreach (['model', 'migration', 'service', 'action', 'component', 'test', 'controller', 'api'] as $shape) {
 $plan = $generator->plan('app.public_consumer', $shape, 'Sample'.ucfirst($shape));
 $consent = [];
 foreach ($plan as $name => $contents) if (is_file($root.'/'.$name)) $consent[$name] = hash_file('sha256', $root.'/'.$name);
 $generator->write($plan, $consent);
}
if ((new Runa\Development\ApiSourceCheck)->check($root) !== []) exit(40);
if ((new Runa\Support\Modules\Development\ModuleContractSuite($root.'/src', false))->source('app.public_consumer') !== []) exit(43);
foreach (['auth' => 'IdentityUpgrades', 'audit' => 'AuditUpgrades', 'permissions' => 'PermissionsUpgrades'] as $code => $provider) {
 $namespace = 'Runa\\Modules\\'.ucfirst($code).'\\';
 // Source diagnostics do not bypass the registry-aware runtime loader.
 require $argv[1].'/src/runa/modules/'.$code.'/backend/Definitions/'.$provider.'.php';
 $class = $namespace.'Definitions\\'.$provider;
 $steps = (new $class)->upgrades();
 $last = end($steps);
 $manifest = json_decode(file_get_contents($argv[1].'/src/runa/modules/'.$code.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
 if ($last->to !== $manifest['version'] || $last->key !== 'platform_delivery') exit(41);
 if (!(new Runa\Support\VersionCompatibility)->supports($manifest['platform'], '0.6.0')) exit(42);
}
""",
        str(tmp_path),
    )
    assert result.returncode == 0, result.stderr
