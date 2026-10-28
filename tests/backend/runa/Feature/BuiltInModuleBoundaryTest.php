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
            self::assertSame($migration['sha256'], hash_file('sha256', $root.'/'.$migration['path']));
        }
        // Start at installation to validate every retained edge fingerprint, including
        // completed older edges that a 1.0.2 -> current plan no longer schedules.
        $input = ['platform' => trim(file_get_contents($root.'/VERSION')), 'modules' => $modules, 'module_steps' => $baseline['module_steps']];
        foreach ($input['modules'] as &$module) {
            $module['installed'] = null;
        }
        unset($module);
        $process = $this->planner($root, $input);
        $process->mustRun();
        foreach (json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR) as $module) {
            self::assertSame($module['module'] === 'runa.auth' ? ['execution_layout', 'frontend_contracts', 'account_orchestration'] : [], $module['pending_upgrades']);
        }
        // Installed-source planning must schedule the remaining compatible source-only edges.
        $input['modules'] = $modules;
        $process = $this->planner($root, $input);
        $process->mustRun();
        foreach (json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR) as $module) {
            self::assertSame($module['module'] === 'runa.auth' ? ['execution_layout', 'frontend_contracts', 'account_orchestration'] : [], $module['pending_upgrades']);
        }
        foreach ($input['modules'] as &$module) {
            $module['installed'] = null;
        }
        unset($module);
        $input['module_steps'][0]['fingerprint'] = str_repeat('0', 64);
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
