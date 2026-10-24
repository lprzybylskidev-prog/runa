<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Runa\Http\Controllers\Controller;
use Runa\Http\Controllers\WelcomePageController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class SpaController extends Controller
{
    public function __invoke(WelcomePageController $welcome): BinaryFileResponse|RedirectResponse
    {
        return $welcome();
    }
}
