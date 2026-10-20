<?php

namespace Runa\Management;

final class KnowledgeCommand extends ToolingCommand
{
    protected $signature = 'runa:knowledge {action} {query?} {--device=} {--top-k=5} {--explain} {--document-type=} {--scope=} {--roadmap-id=} {--status=} {--source-path=} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Development-only knowledge indexing, retrieval and diagnostics';

    protected string $tool = 'knowledge';
}
