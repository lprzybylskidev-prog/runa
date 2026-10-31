<?php

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementBinding;
use Runa\Contracts\Modules\ManagementCommandDefinition;
use Runa\Contracts\Modules\ManagementOperation;
use Runa\Contracts\Modules\ManagementPage;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Contracts\Modules\ManagementWidget;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Management\ApplicationManagement;
use Runa\ModuleFramework\Management\ManagementBuilds;
use Runa\ModuleFramework\Management\ManagementPages;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Admin\Queries\Projection;
use Runa\Modules\Auth\Management\AdministrationPages;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Tests\Fixtures\ModuleSources;
use Tests\PlatformTestCase;

uses(PlatformTestCase::class);

beforeEach(function (): void {
    app(ModuleRegistry::class)->synchronize('runa');
    $this->sources = new ModuleSources;
    $namespace = 'Fixture'.bin2hex(random_bytes(6));
    $directory = $this->sources->root.'/app/Management/'.$namespace;
    mkdir($directory, 0775, true);
    foreach (['Provider', 'Handler'] as $name) {
        $file = $directory.'/'.$name.'.php';
        file_put_contents($file, str_replace('FIXTURE_NAMESPACE', $namespace, file_get_contents(__DIR__.'/../../Fixtures/Management/Admin'.$name.'.php.stub')));
        require $file;
    }
    $this->providerClass = 'App\\Management\\'.$namespace.'\\Provider';
    $this->handlerClass = 'App\\Management\\'.$namespace.'\\Handler';
    app()->bind($this->handlerClass, $this->handlerClass);
    $this->registration = new ApplicationManagement(app(), $this->sources->root);
    $class = $this->providerClass;
    $this->provider = new $class;
    $this->registration->register($this->provider);
    $this->declarations = new ManagementPages(app(ModuleRegistry::class), app(ModuleDeclarations::class), app(ModuleAvailability::class), $this->registration, app(ModuleApis::class), $this->sources->root);
    $this->authority = new class implements OperationAuthorizer
    {
        public array $grants = ['runa.admin.enter'];

        public function allows(OperationContext $context, string $permission): bool
        {
            return in_array($permission, $this->grants, true);
        }
    };
    $this->projection = new Projection($this->declarations, $this->authority);
});

afterEach(function (): void {
    $this->sources?->remove();
});

it('projects an independent App page and removes forbidden fields values choices queries and options', function (): void {
    $bootstrap = $this->projection->bootstrap();
    expect($bootstrap['owners'])->toHaveCount(1)->and($bootstrap['owners'][0]['id'])->toBe('app')
        ->and($bootstrap['owners'][0]['default_page'])->toBe('users')->and($bootstrap['owners'][0]['pages'])->toHaveCount(1)
        ->and($bootstrap['dashboard']['widgets'])->toBe([])->and($this->handlerClass::$calls)->toBe(0);
    $page = $this->projection->page('app', 'users');
    expect(array_column($page['sections'][0]['fields'], 'key'))->toBe(['name', 'token', 'allowed_reference'])
        ->and($page['sections'][0]['readonly'])->toBeTrue()->and($page['sections'][0]['commands'])->toBe([])
        ->and($page['sections'][1]['key'])->toBe('app.extra.details')
        ->and(json_encode($page))->not->toContain('private_choice', 'hidden', '"key":"reference"');
    $record = $this->projection->read('app', 'users', 'profile', []);
    expect($record['values'])->toBe(['name' => 'Visible', 'token' => ['set' => true]])
        ->and(json_encode($record))->not->toContain('private-value-marker', 'unlisted-marker');
    $list = $this->projection->read('app', 'users', 'profile', ['kind' => 'page', 'filters' => ['name' => 'Visible'], 'sort' => [['key' => 'name', 'direction' => 'asc']]]);
    expect($list['total'])->toBeNull()->and($list['items'][0]['values'])->toBe($record['values']);
    $calls = $this->handlerClass::$calls;
    foreach ([['filters' => ['hidden' => 'probe']], ['sort' => [['key' => 'hidden', 'direction' => 'asc']]], ['kind' => 'options'], ['selection' => ['internal-id']], ['limit' => 101]] as $query) {
        expect(fn () => $this->projection->read('app', 'users', 'profile', $query))->toThrow(OperationRejected::class);
    }
    expect($this->handlerClass::$calls)->toBe($calls);
    expect(fn () => $this->projection->page('app', 'hidden'))->toThrow(OperationRejected::class);
    expect(fn () => $this->projection->read('app', 'users', 'missing', []))->toThrow(OperationRejected::class);
});

it('refuses read-only and unknown submitted fields before any owning write executes', function (): void {
    $generation = app(ModuleRegistry::class)->snapshot()->generation;
    $section = $this->provider->section();
    $input = ['command' => 'update', 'public_id' => null, 'revision' => 'revision-1', 'values' => ['name' => 'Updated']];
    expect(fn () => $this->projection->writeInput('app', $section, $input, $generation))->toThrow(OperationRejected::class);
    $this->authority->grants[] = 'runa.auth.profile.update';
    expect($this->projection->writeInput('app', $section, $input, $generation)->values)->toBe(['name' => 'Updated']);
    foreach (['hidden', 'token', 'unknown'] as $field) {
        $input['values'] = [$field => 'untrusted'];
        expect(fn () => $this->projection->writeInput('app', $section, $input, $generation))->toThrow(OperationRejected::class);
    }
    expect($this->handlerClass::$calls)->toBe(0);
});

it('refuses ambiguous form intent and implicit management proof actions', function (): void {
    $operation = new ManagementOperation(new ApiOperation('update', 'runa.auth.profile.update'));
    expect(fn () => new ManagementCommandDefinition('delete', 'Delete', $operation, 'profile', intent: 'delete'))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ManagementCommandDefinition('save', 'Save', $operation, 'profile', intent: 'unknown'))->toThrow(InvalidArgumentException::class);
    $protected = new ManagementOperation(new ApiOperation('update', 'runa.auth.profile.update'), stepUp: 'both');
    expect(fn () => new ManagementCommandDefinition('save', 'Save', $protected, 'profile'))->toThrow(InvalidArgumentException::class);
    expect(new ManagementCommandDefinition('save', 'Save', $protected, 'profile', proofAction: 'runa.auth.profiles.manage'))->toBeInstanceOf(ManagementCommandDefinition::class);
});

it('isolates invalid declarations and freezes App registrations without shadowing owners', function (): void {
    $class = $this->providerClass;
    $class::$duplicate = true;
    expect($this->declarations->discover()['diagnostics'])->toBe(['app' => 'admin_declaration_unavailable'])
        ->and($this->projection->bootstrap()['owners'])->toBe([]);
    expect(fn () => $this->registration->register(new $class))->toThrow(InvalidArgumentException::class);
    $class::$duplicate = false;
    $class::$component = 'app.foreign.component';
    expect($this->declarations->discover()['diagnostics'])->toBe(['app' => 'admin_declaration_unavailable']);
    $class::$component = 'app.missing';
    expect($this->declarations->discover()['diagnostics'])->toBe(['app' => 'admin_declaration_unavailable']);
    $class::$component = null;
    expect($this->projection->bootstrap()['owners'])->toHaveCount(1);
    app()->offsetUnset($this->handlerClass);
    expect(fn () => $this->declarations->handler('app', new ManagementBinding('profile', $this->handlerClass, [])))->toThrow(InvalidArgumentException::class);
});

it('rejects mixed generations at discovery and after an owning read', function (): void {
    $class = $this->providerClass;
    $class::$changed = true;
    expect(fn () => $this->projection->bootstrap())->toThrow(OperationRejected::class);
    $class::$changed = false;
    $this->handlerClass::$changed = true;
    expect(fn () => $this->projection->read('app', 'users', 'profile', []))->toThrow(OperationRejected::class);
});

it('refuses raw secrets and redacts owning failures and widget diagnostics', function (): void {
    $this->handlerClass::$rawSecret = true;
    expect(fn () => $this->projection->read('app', 'users', 'profile', []))->toThrow(OperationRejected::class);
    $this->handlerClass::$failure = true;
    expect(fn () => $this->projection->read('app', 'users', 'profile', []))->toThrow(OperationRejected::class);
    $this->providerClass::$hiddenWidget = false;
    $bootstrap = $this->projection->bootstrap();
    expect($bootstrap['dashboard']['widgets'][0]['diagnostic'])->toBe('admin_widget_unavailable')
        ->and($bootstrap['dashboard']['widgets'][0]['values'])->toBe([])
        ->and(json_encode($bootstrap))->not->toContain('private-exception-marker', 'private-secret-marker');
});

it('bounds management input DTOs without accepting internal IDs or executable values', function (): void {
    foreach ([fn () => new ManagementRead('42'), fn () => new ManagementRead(filters: ['name' => new stdClass]),
        fn () => new ManagementRead(sort: [['key' => 'name', 'direction' => 'DROP']]), fn () => new ManagementRead(limit: 0)] as $invalid) {
        expect($invalid)->toThrow(InvalidArgumentException::class);
    }
});

it('authorizes one lookup without admitting its denied sibling or mismatched field results', function (): void {
    $result = $this->projection->read('app', 'users', 'profile', ['kind' => 'options', 'field' => 'allowed_reference']);
    expect($result['field'])->toBe('allowed_reference')->and($result['items'][0]['label'])->toBe('Private option');
    $calls = $this->handlerClass::$calls;
    foreach (['reference', 'unknown', 'name'] as $field) {
        try {
            $this->projection->read('app', 'users', 'profile', ['kind' => 'options', 'field' => $field]);
        } catch (OperationRejected $error) {
            expect($error->reason)->toBe('admin_lookup_unavailable');

            continue;
        }
        test()->fail('Unauthorized lookup succeeded.');
    }
    expect($this->handlerClass::$calls)->toBe($calls);
    $this->handlerClass::$optionField = 'reference';
    expect(fn () => $this->projection->read('app', 'users', 'profile', ['kind' => 'options', 'field' => 'allowed_reference']))->toThrow(OperationRejected::class);
});

it('isolates an unavailable binding from a sibling page and preserves typed widget freshness', function (): void {
    $this->providerClass::$optionalPage = true;
    expect($this->projection->bootstrap()['owners'][0]['pages'])->toHaveCount(2);
    $page = $this->projection->page('app', 'optional');
    expect($page['sections'][0]['diagnostic'])->toBe('admin_binding_unavailable')
        ->and($page['sections'][0]['readonly'])->toBeTrue()->and($page['sections'][0]['record'])->toBeNull();
    expect($this->projection->read('app', 'users', 'profile', [])['values']['name'])->toBe('Visible');
    expect(fn () => $this->projection->read('app', 'optional', 'remote', []))->toThrow(OperationRejected::class);
    $this->providerClass::$hiddenWidget = false;
    $this->handlerClass::$observedAt = '2020-01-01T00:00:00Z';
    $this->handlerClass::$expiresAt = '2020-01-02T00:00:00Z';
    $widget = $this->projection->bootstrap()['dashboard']['widgets'][0];
    expect($widget['observed_at'])->toBe('2020-01-01T00:00:00Z')
        ->and($widget['expires_at'])->toBe('2020-01-02T00:00:00Z')
        ->and($widget['diagnostic'])->toBe('admin_widget_stale')->and($widget['values'])->toBe(['count' => 3]);
    $this->handlerClass::$observedAt = null;
    $widget = $this->projection->bootstrap()['dashboard']['widgets'][0];
    expect($widget['values'])->toBe([])->and($widget['diagnostic'])->toBe('admin_widget_unavailable');
});

it('projects owned component evidence at every level and isolates missing stale and malformed builds', function (): void {
    $directory = $this->sources->root.'/app/resources';
    mkdir($directory.'/js/components/admin', 0775, true);
    mkdir($this->sources->root.'/public/build', 0775, true);
    file_put_contents($directory.'/js/components/admin/panel.vue', '<template>Panel</template>');
    file_put_contents($directory.'/js/components/admin/control.vue', '<template>Control</template>');
    $this->providerClass::$component = 'app.panel';
    $this->providerClass::$nestedComponent = 'app.control';
    $this->providerClass::$hiddenWidget = false;
    $missing = $this->projection->page('app', 'users');
    expect($missing['component'])->toBe(['key' => 'app.panel', 'owner' => 'app', 'manifest_hash' => null, 'build_id' => null])
        ->and($missing['sections'][0]['component']['build_id'])->toBeNull()
        ->and($missing['sections'][1]['component'])->toBeNull();
    $evidence = (new ManagementBuilds($this->sources->root))->evidence($directory, null);
    $manifest = ['schema_version' => 1, 'owners' => ['app' => ['manifest_hash' => null, ...$evidence, 'components' => ['app.control', 'app.panel']]]];
    $file = $this->sources->root.'/public/build/runa-components.json';
    file_put_contents($file, json_encode($manifest, JSON_THROW_ON_ERROR));
    $page = $this->projection->page('app', 'users');
    expect($page['revision'])->not->toBe($missing['revision'])
        ->and($page['component']['build_id'])->toBe($evidence['build_id'])
        ->and($page['sections'][0]['component']['build_id'])->toBe($evidence['build_id'])
        ->and($page['sections'][0]['fields'][0]['component']['key'])->toBe('app.control')
        ->and($this->projection->bootstrap()['dashboard']['widgets'][0]['component']['build_id'])->toBe($evidence['build_id'])
        ->and(json_encode($page))->not->toContain($directory, 'sources', 'panel.vue');
    // Imported helpers also change identity, not just the entry component bytes.
    file_put_contents($directory.'/js/helper.ts', 'export const changed = true;');
    $stale = $this->projection->page('app', 'users');
    expect($stale['component']['build_id'])->toBeNull()
        ->and($stale['revision'])->not->toBe($page['revision'])
        ->and($this->projection->read('app', 'users', 'profile', [])['values']['name'])->toBe('Visible');
    file_put_contents($file, '{malformed-private-build-marker');
    expect($this->projection->page('app', 'users')['component']['build_id'])->toBeNull();
    unlink($file);
    symlink($directory.'/js/helper.ts', $file);
    expect($this->projection->page('app', 'users')['component']['build_id'])->toBeNull();
});

it('projects explicit collection intent without executing a handler and rejects invalid view declarations', function (): void {
    expect($this->projection->page('app', 'users')['sections'][0]['view'])->toBe('record');
    $this->providerClass::$view = 'table';
    expect($this->projection->page('app', 'users')['sections'][0]['view'])->toBe('table')
        ->and($this->handlerClass::$calls)->toBe(0);
    $this->providerClass::$view = 'arbitrary';
    expect($this->declarations->discover()['diagnostics'])->toBe(['app' => 'admin_declaration_unavailable']);
});

it('retains bilingual management metadata without selecting a request language', function (): void {
    $text = ['pl' => 'Profil użytkownika', 'en' => 'User profile'];
    $section = new ManagementSection('profile', $text, 'runa.admin.enter', null, 'profile', []);
    $page = new ManagementPage('profile', $text, 'runa.admin.enter', [$section], group: $text);
    $command = new ManagementCommandDefinition('save', $text, new ManagementOperation(new ApiOperation('save', 'runa.admin.enter')), 'profile');
    $widget = new ManagementWidget('profile', $text, 'runa.admin.enter', 'profile');
    expect($page->title)->toBe($text)->and($page->group)->toBe($text)
        ->and($section->title)->toBe($text)->and($command->title)->toBe($text)->and($widget->title)->toBe($text);
    foreach ([['pl' => 'Profil'], ['pl' => '', 'en' => 'Profile'], ['pl' => [], 'en' => 'Profile'], ['pl' => 'Profil', 'en' => 'Profile', 'de' => 'Profil']] as $invalid) {
        expect(fn () => new ManagementPage('profile', $invalid, 'runa.admin.enter', []))->toThrow(InvalidArgumentException::class);
    }
    expect(AdministrationPages::field('name', 'Name')->field->names)->toBe(['pl' => 'Nazwa', 'en' => 'Name'])
        ->and(Runa\Modules\Permissions\Management\AdministrationPages::field('roles', 'Ordinary roles', 'uuid')->field->names)->toBe(['pl' => 'Zwykłe role', 'en' => 'Ordinary roles']);
    $projected = $this->projection->page('app', 'users');
    expect($projected['sections'][0]['fields'][0]['label'])->toBe($this->provider->section()->fields[0]->field->names);
});

it('requires separate table export authority before reading and excludes sensitive fields', function (): void {
    expect($this->projection->page('app', 'users')['sections'][0]['export_allowed'])->toBeFalse();
    $calls = $this->handlerClass::$calls;
    expect(fn () => $this->projection->read('app', 'users', 'profile', ['kind' => 'page', 'export' => true]))->toThrow(OperationRejected::class);
    expect($this->handlerClass::$calls)->toBe($calls);
    $this->authority->grants[] = 'runa.permissions.tables.export';
    expect($this->projection->page('app', 'users')['sections'][0]['export_allowed'])->toBeTrue();
    $export = $this->projection->read('app', 'users', 'profile', ['kind' => 'page', 'export' => true]);
    expect($export['items'][0]['values'])->toBe(['name' => 'Visible']);
    array_pop($this->authority->grants);
    $calls = $this->handlerClass::$calls;
    expect(fn () => $this->projection->read('app', 'users', 'profile', ['kind' => 'page', 'export' => true]))->toThrow(OperationRejected::class);
    expect($this->handlerClass::$calls)->toBe($calls);
});
