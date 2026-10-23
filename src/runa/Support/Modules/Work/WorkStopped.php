<?php

namespace Runa\Support\Modules\Work;

use RuntimeException;

/** Framework control flow: thrown only at a module-declared safe checkpoint. */
final class WorkStopped extends RuntimeException {}
