<?php

namespace CedricKassen\UserThemes\Services;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Models\User;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Throwable;

class UserThemeService
{
    public const CUSTOMIZATION_KEY = 'theme';

    // Mirrors the colors the panel registers in App\Providers\Filament\FilamentServiceProvider
    public const BASE_COLORS = [
        'danger' => Color::Red,
        'gray' => Color::Zinc,
        'info' => Color::Sky,
        'primary' => Color::Blue,
        'success' => Color::Green,
        'warning' => Color::Amber,
    ];

    /** @var array<string, Panel|null> */
    private static array $themePanels = [];

    /** @return Collection<int, Plugin> */
    public function getInstalledThemes(): Collection
    {
        return Plugin::orderBy('load_order')->get()
            ->filter(fn (Plugin $plugin) => $plugin->isTheme() && in_array($plugin->status, [PluginStatus::Enabled, PluginStatus::Disabled]));
    }

    /** @return Collection<int, Plugin> */
    public function getEnabledThemes(Panel $panel): Collection
    {
        return $this->getInstalledThemes()->filter(fn (Plugin $plugin) => $plugin->shouldLoad($panel->getId()));
    }

    /** @return array<string, string> */
    public function getThemeOptions(Panel $panel): array
    {
        return $this->getInstalledThemes()
            ->filter(fn (Plugin $plugin) => $this->getThemePanel($plugin, $panel) !== null)
            ->mapWithKeys(fn (Plugin $plugin) => [$plugin->id => $plugin->name])
            ->all();
    }

    public function getSelectedTheme(User $user): ?Plugin
    {
        $id = Arr::get($user->getCustomization(), self::CUSTOMIZATION_KEY);

        return is_string($id) ? $this->getInstalledThemes()->firstWhere('id', $id) : null;
    }

    /**
     * Applies the colors, fonts and css of the given theme to the panel and resets
     * everything the panel's enabled themes changed that the given theme doesn't.
     */
    public function applyTheme(Plugin $theme, Panel $panel): void
    {
        $themePanel = $this->getThemePanel($theme, $panel);
        if (!$themePanel) {
            return;
        }

        $enabledThemePanels = $this->getEnabledThemes($panel)
            ->map(fn (Plugin $plugin) => $this->getThemePanel($plugin, $panel))
            ->filter()
            ->values();

        $themePanels = $enabledThemePanels->concat([$themePanel]);

        $themeColors = $themePanel->getColors();
        $changedColors = $enabledThemePanels->flatMap(fn (Panel $enabledThemePanel) => $enabledThemePanel->getColors())->all();
        FilamentColor::register(array_merge(array_intersect_key(self::BASE_COLORS, $changedColors), $themeColors));

        if ($themePanels->contains(fn (Panel $p) => $p->hasCustomFontFamily())) {
            $panel->font($themePanel->getFontFamily(), $themePanel->getFontUrl(), $themePanel->getFontProvider(), $themePanel->getFontPreload());
        }

        if ($themePanels->contains(fn (Panel $p) => $p->hasCustomMonoFontFamily())) {
            $panel->monoFont($themePanel->getMonoFontFamily(), $themePanel->getMonoFontUrl(), $themePanel->getMonoFontProvider(), $themePanel->getMonoFontPreload());
        }

        if ($themePanels->contains(fn (Panel $p) => $p->hasCustomSerifFontFamily())) {
            $panel->serifFont($themePanel->getSerifFontFamily(), $themePanel->getSerifFontUrl(), $themePanel->getSerifFontProvider(), $themePanel->getSerifFontPreload());
        }

        if ($themePanels->contains(fn (Panel $p) => filled($p->getViteTheme()))) {
            $panel->viteTheme([])->theme($themePanel->getTheme());
        }
    }

    /**
     * Registers the theme on an empty panel, so its changes can be read without affecting the real panel.
     */
    private function getThemePanel(Plugin $theme, Panel $panel): ?Panel
    {
        $key = $panel->getId() . ':' . $theme->id;

        if (!array_key_exists($key, self::$themePanels)) {
            try {
                $class = $theme->fullClass();

                $themePanel = Panel::make()
                    ->id($panel->getId())
                    ->plugin(new $class());

                // Resolving the theme throws if its css hasn't been built
                $themePanel->getTheme();

                self::$themePanels[$key] = $themePanel;
            } catch (Throwable $exception) {
                report($exception);

                self::$themePanels[$key] = null;
            }
        }

        return self::$themePanels[$key];
    }
}
