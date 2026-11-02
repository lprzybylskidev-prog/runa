<?php

namespace Runa\Modules\Auth\Management;

/** Read metadata only; multipart mutations retain Auth's native HTTP protocol. */
final readonly class AvatarSection extends SubjectSection
{
    protected const KEY = 'avatar';
}
