<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class BuiltInModuleBoundaryTest extends TestCase
{
    public function test_current_initialization_is_direct_and_does_not_execute_published_history(): void
    {
        $root = dirname(__DIR__, 4);
        $process = $this->planner($root, ['platform' => '0.7.0', 'modules' => [], 'module_steps' => []]);
        $process->mustRun();
        $plan = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['runa.audit', 'runa.auth', 'runa.permissions', 'runa.admin'], array_column($plan, 'module'));
        foreach ($plan as $module) {
            self::assertNull($module['from']);
            self::assertSame(['initialize'], $module['pending_upgrades']);
            self::assertCount(1, $module['upgrades']);
            self::assertSame($module['to'], $module['upgrades'][0]['to']);
            self::assertTrue($module['upgrades'][0]['transactional']);
        }
    }

    public function test_current_update_fingerprints_refuse_tampering_and_unsupported_versions(): void
    {
        $root = dirname(__DIR__, 4);
        $modules = $this->currentSchemaState($root);
        $input = ['platform' => '0.7.0', 'modules' => $modules, 'module_steps' => []];
        $process = $this->planner($root, $input);
        $process->mustRun();
        $plan = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        foreach ($plan as $module) {
            self::assertSame(['current_initialization'], $module['pending_upgrades']);
        }
        $auth = array_values(array_filter($plan, fn ($module) => $module['module'] === 'runa.auth'))[0];
        $input['module_steps'][] = ['module' => 'runa.auth', 'step' => 'upgrade:current_initialization:6.0.0:'.$auth['to'], 'fingerprint' => str_repeat('0', 64), 'status' => 'succeeded'];
        $process = $this->planner($root, $input);
        self::assertSame(1, $process->run());
        self::assertSame('', $process->getOutput());
        $input['module_steps'] = [];
        $input['modules']['runa.auth']['installed'] = '5.0.2';
        $process = $this->planner($root, $input);
        self::assertSame(1, $process->run());
        self::assertStringContainsString('Module target preflight failed', $process->getErrorOutput());
    }

    public function test_published_admin_retains_preparation_but_replaced_core_migrations_refuse(): void
    {
        $root = dirname(__DIR__, 4);
        $modules = $this->currentSchemaState($root);
        $modules['runa.admin']['installed'] = '1.0.0';
        $input = ['platform' => '0.7.0', 'modules' => $modules, 'module_steps' => []];
        $process = $this->planner($root, $input);
        $process->mustRun();
        $plan = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $admin = array_values(array_filter($plan, fn ($module) => $module['module'] === 'runa.admin'))[0];
        self::assertSame(['published_1_0_0'], $admin['pending_upgrades']);
        self::assertSame([], $admin['migrations']);

        // Immutable historical fixture is evidence, never a claim that today's
        // replaced CREATE definitions can convert a published database.
        $baseline = json_decode(file_get_contents(__DIR__.'/../Fixtures/modules/history-before-execution-layout.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($baseline['migrations'] as $migration) {
            $history = new Process(['git', 'show', $baseline['baseline_commit'].':'.$migration['path']], $root);
            $history->mustRun();
            self::assertSame($migration['sha256'], hash('sha256', $history->getOutput()));
            $code = explode('/modules/', $migration['path'])[1];
            $input['module_steps'][] = ['module' => 'runa.'.explode('/', $code)[0], 'step' => 'migration:'.basename($migration['path'], '.php'), 'fingerprint' => $migration['sha256'], 'status' => 'succeeded'];
        }
        $process = $this->planner($root, $input);
        self::assertSame(1, $process->run());
        self::assertStringContainsString('Module target preflight failed', $process->getErrorOutput());
        self::assertSame('', $process->getOutput());
    }

    /** @return array<string, array{installed: string, version: string, registered: bool, required: array<string, string>}> */
    private function currentSchemaState(string $root): array
    {
        $modules = [];
        foreach (['auth' => '6.0.0', 'audit' => '1.1.1', 'permissions' => '2.1.1', 'admin' => '1.1.0'] as $code => $installed) {
            $manifest = json_decode(file_get_contents($root.'/src/runa/modules/'.$code.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
            $modules['runa.'.$code] = ['installed' => $installed, 'version' => $manifest['version'], 'registered' => true, 'required' => $manifest['dependencies']['required']];
        }

        return $modules;
    }

    /** @param array<string, mixed> $input */
    private function planner(string $root, array $input): Process
    {
        $process = new Process([PHP_BINARY, $root.'/distribution/module-plan.php', $root.'/src', $root.'/src'], sys_get_temp_dir());
        $process->setInput(json_encode($input, JSON_THROW_ON_ERROR));

        return $process;
    }
}
