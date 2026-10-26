<?php

namespace Runa\Contracts\Modules;

enum HttpAccess: string
{
    case Public = 'public';
    case Authenticated = 'authenticated';
    case Restricted = 'restricted';
}
