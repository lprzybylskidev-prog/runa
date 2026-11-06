<?php

namespace Runa\Modules\Auth\Support;

use Runa\Support\Modules\Api\OperationRejected;

/** Internal structural decoder shared by native preference writes and Admin transport. */
final class DeliveryInput
{
    /** @return list<array{module:string,key:string,enabled:bool,recipients:list<string>}> */
    public static function decode(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > 128) {
            throw self::invalid();
        }
        $result = [];
        foreach ($value as $entry) {
            if (! is_array($entry) || count($entry) !== 4 || ! is_string($entry['module'] ?? null) || ! is_string($entry['key'] ?? null) || ! is_bool($entry['enabled'] ?? null) || ! is_array($entry['recipients'] ?? null) || ! array_is_list($entry['recipients']) || ($entry['enabled'] && $entry['recipients'] === []) || count(array_unique($entry['recipients'], SORT_REGULAR)) !== count($entry['recipients']) || count($entry['recipients']) > 100) {
                throw self::invalid();
            }
            $recipients = [];
            foreach ($entry['recipients'] as $recipient) {
                if (! is_string($recipient)) {
                    throw self::invalid();
                }
                $recipients[] = $recipient;
            }
            $result[] = ['module' => $entry['module'], 'key' => $entry['key'], 'enabled' => $entry['enabled'], 'recipients' => $recipients];
        }

        return $result;
    }

    private static function invalid(): OperationRejected
    {
        return new OperationRejected('preference_invalid', 'runa.auth', 'preferences.admin_write', 422);
    }
}
