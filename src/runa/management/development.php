<?php

use Runa\Management\AnalyseCommand;
use Runa\Management\DevelopmentCommand;
use Runa\Management\DoctorCommand;
use Runa\Management\FormatCommand;
use Runa\Management\HookCommand;
use Runa\Management\KnowledgeCommand;
use Runa\Management\LintCommand;
use Runa\Management\QualityCommand;
use Runa\Management\SecurityCommand;
use Runa\Management\SourceCheckCommand;
use Runa\Management\TestCommand;
use Runa\Management\TypecheckCommand;
use Runa\Management\XdebugCommand;

// Optional development registry; omitted from the production management artifact.
return [
    DevelopmentCommand::class,
    DoctorCommand::class,
    QualityCommand::class,
    KnowledgeCommand::class,
    HookCommand::class,
    SecurityCommand::class,
    XdebugCommand::class,
    SourceCheckCommand::class,
    FormatCommand::class,
    LintCommand::class,
    TypecheckCommand::class,
    AnalyseCommand::class,
    TestCommand::class,
];
