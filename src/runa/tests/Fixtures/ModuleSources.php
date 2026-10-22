<?php

namespace Tests\Fixtures;

use Illuminate\Support\Facades\File;
use Runa\Support\Modules\ModuleManifest;

final class ModuleSources
{
    public readonly string $root;

    public function __construct()
    {
        $this->root = sys_get_temp_dir().'/runa-module-sources-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0775, true);
    }

    /** @return array<string, mixed> */
    public function add(string $owner, string $code, string $kind = 'optional', string $version = '0.1.0'): array
    {
        $root = $this->root.'/'.$owner.'/modules/'.$code;
        mkdir($root.'/backend/Definitions', 0775, true);
        mkdir($root.'/docs', 0775, true);
        mkdir($root.'/tests', 0775, true);
        $prefix = ($owner === 'app' ? 'App' : 'Runa').'\\Modules\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', $code)));
        $definition = [
            'schema_version' => 1, 'id' => $owner.'.'.$code, 'kind' => $kind, 'version' => $version,
            'platform' => '^0.4.0', 'framework_api' => '^1.0.0',
            'name' => (object) ['pl' => 'Moduł', 'en' => 'Module'], 'description' => (object) ['pl' => 'Opis', 'en' => 'Description'],
            'dependencies' => (object) ['required' => new \stdClass, 'optional' => new \stdClass],
            'providers' => [], 'declarations' => (object) array_fill_keys(ModuleManifest::CATEGORIES, []),
            'docs' => ['docs/README.md'], 'tests' => ['tests'],
        ];
        $definition['declarations']->lifecycle = [$prefix.'\\Definitions\\WorkPolicy'];
        file_put_contents($root.'/backend/Definitions/WorkPolicy.php', '<?php namespace '.$prefix.'\\Definitions; final class WorkPolicy {}');
        file_put_contents($root.'/docs/README.md', '# Fixture');
        $this->write($owner, $code, $definition);

        return $definition;
    }

    /** @param array<string, mixed> $definition */
    public function write(string $owner, string $code, array $definition): void
    {
        file_put_contents($this->root.'/'.$owner.'/modules/'.$code.'/module.json', json_encode($definition, JSON_THROW_ON_ERROR));
    }

    public function remove(): void
    {
        File::deleteDirectory($this->root);
    }
}
