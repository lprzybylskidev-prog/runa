<?php

namespace Tests\Fixtures;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/** Source-owned disposable optional fixture using the actual runtime discovery root. */
final class OperationalModule
{
    public readonly string $id;

    public readonly string $directory;

    public function __construct()
    {
        $sources = new ModuleSources;
        $code = 'operational'.bin2hex(random_bytes(5));
        $this->id = 'app.'.$code;
        $this->directory = base_path('app/modules/'.$code);
        $manifest = $sources->add('app', $code);
        $manifest['platform'] = '>=0.7.0 <0.8.0';
        $manifest['dependencies']->optional = (object) ['runa.audit' => '^1.0.0'];
        $namespace = 'App\\Modules\\'.ucfirst($code).'\\Definitions';
        foreach (['health' => 'Checks', 'settings' => 'Settings', 'integrations' => 'Integrations', 'jobs' => 'Jobs'] as $category => $class) {
            $manifest['declarations']->$category = [$namespace.'\\'.$class];
        }
        $sources->write('app', $code, $manifest);
        File::copyDirectory($sources->root.'/app/modules/'.$code, $this->directory);
        $sources->remove();
        $files = [
            'WorkPolicy' => 'final class WorkPolicy extends \\Tests\\Fixtures\\TransitionPolicy { public const ID = '.var_export($this->id, true).'; }',
            'Checks' => 'final class Checks extends \\Tests\\Fixtures\\HealthPolicy { public const ID = '.var_export($this->id, true).'; }',
            'Integrations' => <<<'SOURCE'
final class Integrations implements \Runa\Contracts\Modules\IntegrationProvider {
    public function integrations(): array { return [new \Runa\Contracts\Modules\IntegrationDefinition('history', 'runa.audit', false, ['pl'=>'Historia','en'=>'History'], ['mode'=>'safe'])]; }
    public function validConfiguration(string $key, array $config): bool { return $key === 'history' && $config === ['mode'=>'safe']; }
}
SOURCE,
            'Settings' => <<<'SOURCE'
final class Settings implements \Runa\Contracts\Modules\SettingsProvider, \Runa\Contracts\Modules\SettingsHandler {
    public function settings(): array { return [new \Runa\Contracts\Modules\SettingDefinition('title','string','Initial',['pl'=>'Tytuł','en'=>'Title'],['pl'=>'Tytuł','en'=>'Title']), new \Runa\Contracts\Modules\SettingDefinition('credential','string',null,['pl'=>'Sekret','en'=>'Credential'],['pl'=>'Sekret','en'=>'Credential'],sensitive:true,nullable:true)]; }
    public function valid(array $values): bool { return is_string($values['title']) && strlen($values['title']) < 100; }
    public function handler(): string { return self::class; }
    public function apply(\Runa\Contracts\Modules\SettingsContext $context): bool { return true; }
    public function compensate(\Runa\Contracts\Modules\SettingsContext $context): bool { return true; }
}
SOURCE,
            'Jobs' => <<<'SOURCE'
final class Jobs implements \Runa\Contracts\Modules\JobProvider {
    public function jobs(): array { return [new \Runa\Contracts\Modules\JobDefinition('process',Handler::class,Policy::class,3,2,30,true,'safe_checkpoints_only')]; }
}
SOURCE,
            'Handler' => <<<'SOURCE'
final class Handler implements \Runa\Contracts\Modules\WorkHandler { public function handle(\Runa\Support\Modules\Work\WorkContext $context, array $payload): void {} }
SOURCE,
            'Policy' => <<<'SOURCE'
final class Policy implements \Runa\Contracts\Modules\WorkPolicy {
    public function schemaVersion(): int { return 1; }
    public function pending(\Runa\Contracts\Modules\WorkStatus $work, bool $available): \Runa\Contracts\Modules\WorkDecision { return new \Runa\Contracts\Modules\WorkDecision('run', 'ready'); }
    public function checkpoint(\Runa\Contracts\Modules\WorkStatus $work): \Runa\Contracts\Modules\WorkDecision { return new \Runa\Contracts\Modules\WorkDecision($work->cancellationRequested ? 'cancel' : 'run', 'checkpoint'); }
}
SOURCE,
        ];
        foreach ($files as $name => $body) {
            file_put_contents($this->directory.'/backend/Definitions/'.$name.'.php', '<?php namespace '.$namespace.'; '.$body);
        }
        DB::table('shared.modules')->insert([
            'public_id' => (string) Str::uuid7(), 'module_id' => $this->id, 'owner' => 'app', 'code' => $code,
            'kind' => 'optional', 'registered' => true, 'enabled' => true, 'source_present' => true,
            'source_version' => '0.1.0', 'installed_version' => '0.1.0', 'source_path' => 'app/modules/'.$code,
            'manifest_hash' => hash_file('sha256', $this->directory.'/module.json'),
            'manifest' => json_encode($manifest, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }

    public function remove(): void
    {
        $id = DB::table('shared.modules')->where('module_id', $this->id)->value('id');
        foreach (DB::table('shared.module_work')->where('module_id', $id)->pluck('public_id') as $work) {
            DB::table('shared.jobs')->whereRaw("payload::jsonb ->> 'runa_work' = ?", [$work])->delete();
        }
        DB::table('shared.module_work')->where('module_id', $id)->delete();
        DB::table('shared.module_transitions')->where('target', $this->id)->delete();
        DB::table('shared.modules')->where('module_id', $this->id)->delete();
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
        unset(TransitionPolicy::$prepare[$this->id], TransitionPolicy::$compensate[$this->id], HealthPolicy::$checks[$this->id]);
        File::deleteDirectory($this->directory);
    }
}
