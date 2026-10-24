<?php

namespace CedricKassen\UserThemes\Http\Middleware;

use CedricKassen\UserThemes\Services\UserThemeService;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserTheme
{
    public function __construct(private UserThemeService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = user();
        $panel = Filament::getCurrentPanel();

        if ($user && $panel && $theme = $this->service->getSelectedTheme($user)) {
            $this->service->applyTheme($theme, $panel);
        }

        return $next($request);
    }
}
