<?php

namespace Runa\Contracts\Modules;

interface ApiProvider
{
    public function definition(): ApiDefinition;
}
