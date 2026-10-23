<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\CommandDefinition;
use Runa\Contracts\Modules\CommandProvider;
use Runa\Contracts\Modules\JobDefinition;
use Runa\Contracts\Modules\JobProvider;
use Runa\Contracts\Modules\ScheduleDefinition;
use Runa\Contracts\Modules\ScheduleProvider;

final class EntryWork implements CommandProvider, JobProvider, ScheduleProvider
{
    public function jobs(): array
    {
        return [
            new JobDefinition('security.deliver', SecurityNoticeHandler::class, EntryWorkPolicy::class, 3, 30, 60, false, 'single_smtp_attempt'),
            new JobDefinition('security.remind', SecurityReminderHandler::class, EntryWorkPolicy::class, 3, 30, 60, false, 'bounded_current_deadline'),
            new JobDefinition('profile.deliver', ProfileMailHandler::class, EntryWorkPolicy::class, 3, 30, 60, false, 'single_smtp_attempt'),
            new JobDefinition('sessions.cleanup', SessionCleanupHandler::class, EntryWorkPolicy::class, 3, 30, 60, false, 'bounded_technical_cleanup'),
            new JobDefinition('entry.deliver', EntryMailHandler::class, EntryWorkPolicy::class, 3, 30, 60, false, 'single_smtp_attempt'),
            new JobDefinition('entry.remind', EntryReminderHandler::class, EntryWorkPolicy::class, 3, 30, 60, false, 'locked_token_rotation'),
        ];
    }

    public function commands(): array
    {
        return [new CommandDefinition('security.remind', 'operational', 'security.remind'), new CommandDefinition('sessions.cleanup', 'operational', 'sessions.cleanup'), new CommandDefinition('entry.remind', 'operational', 'entry.remind')];
    }

    public function schedules(): array
    {
        return [new ScheduleDefinition('security.remind', 'security.remind', '* * * * *', 5), new ScheduleDefinition('sessions.cleanup', 'sessions.cleanup', '*/5 * * * *', 5), new ScheduleDefinition('entry.remind', 'entry.remind', '* * * * *', 5)];
    }
}
