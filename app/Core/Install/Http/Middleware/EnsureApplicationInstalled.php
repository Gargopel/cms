<?php

namespace App\Core\Install\Http\Middleware;

use App\Core\Install\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationInstalled
{
    public function __construct(
        protected InstallationState $installationState,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installationState->isInstalled()) {
            return $next($request);
        }

        return redirect()->route('install.welcome');
    }
}
