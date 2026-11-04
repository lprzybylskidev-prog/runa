<?php

namespace Runa\Modules\Auth\Management;

use Runa\Contracts\Modules\ManagementOutcome;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementReview;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\ManagementWriter;
use Runa\Modules\Auth\Contracts\AuthPreferences;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class PreferenceSection implements ManagementResourceReader, ManagementWriter
{
    public function __construct(private ModuleApis $apis) {}

    private function api(): AuthPreferences
    {
        return $this->apis->resolve('runa.auth', AuthPreferences::class, '1.1.0');
    }

    public function read(ManagementRead $input): ManagementRecord
    {
        if ($input->publicId === null) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'preferences.admin_read', 422);
        }
        $state = $this->api()->account($input->publicId);

        return new ManagementRecord($input->publicId, (string) $state['revision'], [
            'locale' => $state['locale'], 'theme' => $state['theme'],
            'delivery' => json_encode(array_map(fn ($entry) => array_intersect_key($entry, array_flip(['module', 'key', 'enabled', 'recipients'])), $state['categories']), JSON_THROW_ON_ERROR),
            'categories' => json_encode($state['categories'], JSON_THROW_ON_ERROR), 'recipients' => json_encode($state['recipients'], JSON_THROW_ON_ERROR),
            'preference_revision' => $state['revision'],
        ]);
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        if ($input->publicId === null || ! ctype_digit($input->revision)) {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'preferences.admin_write', 422);
        }
        if ($input->command === 'save' && count($input->values) === 3 && is_string($input->values['locale'] ?? null)
            && is_string($input->values['theme'] ?? null) && is_string($input->values['delivery'] ?? null)) {
            try {
                $delivery = json_decode($input->values['delivery'], true, flags: JSON_THROW_ON_ERROR);
                if (! is_array($delivery)) {
                    throw new \InvalidArgumentException('Invalid delivery preferences.');
                }
                $this->api()->updateAccount($input->publicId, $input->values['locale'], $input->values['theme'], $delivery, (int) $input->revision);
            } catch (IdentityConflict) {
                throw new OperationRejected('preferences_stale', 'runa.auth', 'preferences.admin_write', 409);
            } catch (\JsonException|\InvalidArgumentException|\TypeError) {
                throw new OperationRejected('admin_input_invalid', 'runa.auth', 'preferences.admin_write', 422);
            }
        } elseif ($input->command === 'reset' && $input->confirmed && count($input->values) === 1
            && ($input->values['preference_revision'] ?? null) === (int) $input->revision) {
            try {
                $this->api()->resetAccount($input->publicId, (int) $input->revision);
            } catch (IdentityConflict) {
                throw new OperationRejected('preferences_stale', 'runa.auth', 'preferences.admin_reset', 409);
            }
        } else {
            throw new OperationRejected('admin_input_invalid', 'runa.auth', 'preferences.admin_write', 422);
        }

        return new ManagementOutcome('committed', $this->read(new ManagementRead($input->publicId)));
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'preferences.admin_read', 404);
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'preferences.admin_write', 404);
    }
}
