<?php

namespace Runa\ModuleFramework;

/** Internal publication comparison; never grants an operation or extends its evidence. */
final class HealthObservations
{
    /** @param array<string, array{fingerprint: string, passed: bool, checked_at: int, expires_at: int, code: string}> $before
     * @param  array<string, array{fingerprint: string, passed: bool, checked_at: int, expires_at: int, code: string}>  $after
     */
    public static function healthyRenewal(array $before, array $after, int $now): bool
    {
        if ($before === [] || count($before) !== count($after)) {
            return false;
        }
        foreach ($before as $id => $old) {
            $new = $after[$id] ?? null;
            if ($new === null || ! $old['passed'] || ! $new['passed'] || $old['code'] !== 'healthy' || $new['code'] !== 'healthy'
                || $old['fingerprint'] !== $new['fingerprint'] || $old['checked_at'] > $now || $old['expires_at'] <= $now
                || $new['checked_at'] < $old['checked_at'] || $new['checked_at'] > $now || $new['expires_at'] <= $now
                || $new['expires_at'] < $old['expires_at']) {
                return false;
            }
        }

        return true;
    }
}
