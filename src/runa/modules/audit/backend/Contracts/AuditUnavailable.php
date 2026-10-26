<?php

namespace Runa\Modules\Audit\Contracts;

/** Persistence was not confirmed. Never report the operation as audited. */
final class AuditUnavailable extends \RuntimeException {}
