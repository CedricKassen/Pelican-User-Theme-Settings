<?php

namespace CedricKassen\UserThemes;

use CedricKassen\UserThemes\Filament\Pages\Auth\EditProfile;
use CedricKassen\UserThemes\Http\Middleware\ApplyUserTheme;
use Filament\Contracts\Plugin;
use Filament\Panel;

class UserThemesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'user-themes';
    }

    public function register(Panel $panel): void
    {
        // Plugins are loaded after the panel config, so this replaces the default profile page.
        $panel->profile(EditProfile::class, false);

        // Panel colors are registered before the session is started, so the user's theme has to be applied afterward.
        $panel->middleware([ApplyUserTheme::class], isPersistent: true);
    }

    public function boot(Panel $panel): void {}
}
