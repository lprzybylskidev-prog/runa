<?php

namespace Runa\Modules\Auth\Queries;

use Illuminate\Database\Connection;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Auth\Contracts\PreferenceProvider;
use Runa\Modules\Auth\Contracts\PreferenceScope;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\PreferenceDeclarations;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class AccountPreferenceSnapshot
{
    public function __construct(private PreferenceDeclarations $declarations, private ModuleRegistry $registry,
        private ModuleDeclarations $sources, private ModuleAvailability $availability) {}

    public function all(Connection $connection, User $user, string $application): array
    {
        $values = [];

        foreach ($this->registry->snapshot()->modules as $record) {
            if (! $record->registered || ! $record->sourcePresent || ! $record->enabled) {
                continue;
            }
            if (! $this->availability->statuses($record->id)[$record->id]->available) {
                continue;
            }
            foreach ($this->sources->classes($record, 'settings') as $class) {
                $provider = $this->sources->make($record, $class);
                if (! $provider instanceof PreferenceProvider) {
                    continue;
                }
                foreach ($provider->preferences() as $definition) {
                    if (count($values) >= 1024) {
                        throw new \RuntimeException('Preference snapshot limit exceeded.');
                    }
                    foreach (array_unique([$application, 'runa']) as $applicationId) {
                        $values[] = $this->one($connection, $user, new PreferenceScope($applicationId, $record->id, $definition->view, $definition->key));
                    }
                }
            }
        }

        return ['account' => $user->public_id, 'revision' => $user->preference_revision,
            'legacy_claimed' => $user->preference_legacy_claimed, 'values' => $values];
    }

    public function one(Connection $connection, User $user, PreferenceScope $scope): array
    {
        [$provider, $definition] = $this->declarations->resolve($scope);
        $row = $connection->table('runa_auth.preferences')->where('user_id', $user->id)->where('scope', $scope->id())->first();
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
