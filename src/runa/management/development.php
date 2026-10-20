<?php

use Runa\Management\AnalyseCommand;
use Runa\Management\DevelopmentCommand;
use Runa\Management\DoctorCommand;
use Runa\Management\FormatCommand;
use Runa\Management\HookCommand;
use Runa\Management\KnowledgeCommand;
use Runa\Management\LintCommand;
use Runa\Management\ProjectCommand;
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
    SecurityCommand::class,
    ProjectCommand::class,
    XdebugCommand::class,
    SourceCheckCommand::class,
    HookCommand::class,
    FormatCommand::class,
    LintCommand::class,
    TypecheckCommand::class,
    AnalyseCommand::class,
    TestCommand::class,
];
