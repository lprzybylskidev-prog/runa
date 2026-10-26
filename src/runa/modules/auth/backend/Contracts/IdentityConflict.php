<?php

namespace Runa\Modules\Auth\Contracts;

/** Stable safe conflict category; never include submitted identifiers in messages. */
final class IdentityConflict extends \RuntimeException {}
