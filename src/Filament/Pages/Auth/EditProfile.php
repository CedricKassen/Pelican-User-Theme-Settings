<?php

namespace CedricKassen\UserThemes\Filament\Pages\Auth;

use App\Enums\TablerIcon;
use App\Filament\Pages\Auth\EditProfile as BaseEditProfile;
use App\Models\Plugin;
use CedricKassen\UserThemes\Services\UserThemeService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;

class EditProfile extends BaseEditProfile
{
    protected bool $themeChanged = false;

    /** @return Tab[] */
    protected function getDefaultTabs(): array
    {
        $tabs = parent::getDefaultTabs();

        foreach ($tabs as $tab) {
            $components = $tab->getDefaultChildComponents();

            if ($tab->getLabel() === trans('profile.tabs.customization') && is_array($components)) {
                $tab->schema([$this->getThemeSection(), ...$components]);
            }
        }

        return $tabs;
    }

    protected function getThemeSection(): Section
    {
        return Section::make(trans('user-themes::strings.theme'))
            ->collapsible()
            ->icon(TablerIcon::Palette)
            ->schema([
                Select::make('user_theme')
                    ->label(trans('user-themes::strings.theme'))
                    ->helperText(trans('user-themes::strings.theme_helper'))
                    ->placeholder(function (UserThemeService $service) {
                        $enabledThemes = $service->getEnabledThemes(Filament::getCurrentOrDefaultPanel());

                        return trans('user-themes::strings.panel_default') . ($enabledThemes->isNotEmpty() ? ' (' . $enabledThemes->map(fn (Plugin $plugin) => $plugin->name)->join(', ') . ')' : '');
                    })
                    ->options(fn (UserThemeService $service) => $service->getThemeOptions(Filament::getCurrentOrDefaultPanel())),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $theme = $data['user_theme'] ?? null;
        unset($data['user_theme']);

        $this->themeChanged = $theme !== app(UserThemeService::class)->getSelectedTheme($this->getUser())?->id;

        // The parent rebuilds the customization from a fixed list of keys, so the theme has to be added back afterward.
        $data = parent::mutateFormDataBeforeSave($data);

        $customization = json_decode($data['customization'], true);
        $customization[UserThemeService::CUSTOMIZATION_KEY] = $theme;
        $data['customization'] = json_encode($customization);

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        $data['user_theme'] = app(UserThemeService::class)->getSelectedTheme($this->getUser())?->id;

        return $data;
    }

    protected function afterSave(): void
    {
        // Colors, fonts and css are rendered into the page head, so a full reload is needed to apply them.
        if ($this->themeChanged) {
            $this->redirect(request()->header('Referer') ?? static::getUrl());
        }
    }
}
