<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class BuiltInModuleBoundaryTest extends TestCase
{
    public function test_installed_history_survives_execution_relocation_and_tampering_is_refused(): void
    {
        $root = dirname(__DIR__, 4);
        $baseline = json_decode(file_get_contents(__DIR__.'/../Fixtures/modules/history-before-execution-layout.json'), true, flags: JSON_THROW_ON_ERROR);
        $modules = [];
        foreach (['audit', 'auth', 'permissions'] as $code) {
            $manifest = json_decode(file_get_contents($root.'/src/runa/modules/'.$code.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
            $modules['runa.'.$code] = ['installed' => '1.0.2', 'version' => '1.0.2', 'registered' => true, 'required' => $manifest['dependencies']['required']];
        }
        foreach ($baseline['migrations'] as $migration) {
            $history = new Process(['git', 'show', $baseline['baseline_commit'].':'.$migration['path']], $root);
            $history->mustRun();
            self::assertSame($migration['sha256'], hash('sha256', $history->getOutput()));
        }
        // Start at installation to validate every retained edge fingerprint, including
        // completed older edges that a 1.0.2 -> current plan no longer schedules.
        // The retained pre-Admin installation upgrades into the declared 0.7 target.
        $input = ['platform' => '0.7.0', 'modules' => $modules, 'module_steps' => $baseline['module_steps']];
        $expected = [
            'runa.admin' => ['initialize', 'standalone_participants', 'creation_release'],
            'runa.auth' => ['execution_layout', 'frontend_contracts', 'account_orchestration', 'platform_delivery', 'frontend_source_checks', 'verification_contracts', 'admin_platform', 'native_transactions', 'section_activity', 'user_details', 'settings_snapshots'],
            'runa.audit' => ['platform_delivery', 'verification_contracts', 'admin_platform', 'admin_history'],
            'runa.permissions' => ['platform_delivery', 'verification_contracts', 'admin_platform', 'native_transactions', 'table_exports'],
        ];
        $process = $this->planner($root, $input);
        $process->mustRun();
        foreach (json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR) as $module) {
            self::assertSame($expected[$module['module']], $module['pending_upgrades']);
        }
        $input['module_steps'] = $baseline['module_steps'];
        $input['module_steps'][] = ['module' => 'runa.auth', 'step' => 'upgrade:native_transactions:1.1.5:2.0.0', 'fingerprint' => str_repeat('0', 64), 'status' => 'succeeded'];
        $process = $this->planner($root, $input);
        self::assertSame(1, $process->run());
        self::assertStringContainsString('Module target preflight failed', $process->getErrorOutput());
        self::assertSame('', $process->getOutput());
    }

    /** @param array<string, mixed> $input */
    private function planner(string $root, array $input): Process
    {
        $process = new Process([PHP_BINARY, $root.'/distribution/module-plan.php', $root.'/src', $root.'/src'], sys_get_temp_dir());
        $process->setInput(json_encode($input, JSON_THROW_ON_ERROR));

        return $process;
    }
}
