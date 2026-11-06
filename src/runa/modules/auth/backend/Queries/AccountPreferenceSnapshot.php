<?php

namespace Runa\Modules\Auth\Queries;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Auth\Contracts\PreferenceDefinition;
use Runa\Modules\Auth\Contracts\PreferenceProvider;
use Runa\Modules\Auth\Contracts\PreferenceScope;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\PreferenceDeclarations;
use Runa\Support\Modules\Api\OperationRejected;

/** @phpstan-type PreferenceRead array{account: string, revision: int, scope: string, version: int, value: mixed, stored: bool, legacy_claimed: bool} */
final readonly class AccountPreferenceSnapshot
{
    public function __construct(private PreferenceDeclarations $declarations, private ModuleRegistry $registry,
        private ModuleAvailability $availability) {}

    /** @return array{account: string, revision: int, legacy_claimed: bool, values: list<PreferenceRead>} */
    public function all(Connection $connection, User $user, string $application): array
    {
        $declarations = [];

        foreach ($this->registry->snapshot()->modules as $record) {
            if (! $record->registered || ! $record->sourcePresent || ! $record->enabled) {
                continue;
            }
            if (! $this->availability->statuses($record->id)[$record->id]->available) {
                continue;
            }
            foreach ($this->declarations->forModule($application, $record->id) as [$provider, $definition]) {
                foreach (array_unique([$application, 'runa']) as $applicationId) {
                    if (count($declarations) >= 1024) {
                        throw new \RuntimeException('Preference snapshot limit exceeded.');
                    }
                    $declarations[] = [new PreferenceScope($applicationId, $record->id, $definition->view, $definition->key), $provider, $definition];
                }
            }
        }
        /** @var Collection<string, object{scope:string,value:string,definition_version:int}> $rows */
        $rows = $declarations === [] ? collect() : $connection->table('runa_auth.preferences')->where('user_id', $user->id)
            ->whereIn('scope', array_map(static fn (array $entry): string => $entry[0]->id(), $declarations))
            ->get(['scope', 'value', 'definition_version'])->keyBy('scope');
        $values = [];
        foreach ($declarations as [$scope, $provider, $definition]) {
            $values[] = $this->project($user, $scope, $provider, $definition, $rows->get($scope->id()));
        }

        return ['account' => $user->public_id, 'revision' => $user->preference_revision,
            'legacy_claimed' => $user->preference_legacy_claimed, 'values' => $values];
    }

    /** @return PreferenceRead */
    public function one(Connection $connection, User $user, PreferenceScope $scope): array
    {
        [$provider, $definition] = $this->declarations->resolve($scope);
        /** @var (object{value: string, definition_version: int}&\stdClass)|null $row */
        $row = $connection->table('runa_auth.preferences')->where('user_id', $user->id)->where('scope', $scope->id())->first();

        return $this->project($user, $scope, $provider, $definition, $row);
    }

    /** @param object{value:string,definition_version:int}|null $row
     * @return PreferenceRead
     */
    private function project(User $user, PreferenceScope $scope, PreferenceProvider $provider, PreferenceDefinition $definition, ?object $row): array
    {
        $value = $definition->default;
        if ($row !== null) {
            $stored = json_decode($row->value, true, flags: JSON_THROW_ON_ERROR);
            if ($row->definition_version !== $definition->version) {
                $stored = $provider->migratePreference($scope->view, $scope->key, $stored, $row->definition_version);
            }
            try {
                $this->declarations->validate($provider, $definition, $stored);
                $value = $stored;
            } catch (OperationRejected) {
                // A removed/invalid old representation resolves to current declared defaults.
            }
        }

        return ['account' => $user->public_id, 'revision' => $user->preference_revision, 'scope' => $scope->id(),
            'version' => $definition->version, 'value' => $value, 'stored' => $row !== null, 'legacy_claimed' => $user->preference_legacy_claimed];
    }
}
