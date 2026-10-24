# User Themes (by Cedric Kassen)

Lets every user pick their own panel theme. A new "Theme" section is added to the "Customization" tab of the profile settings.

## Features

- Users can choose from all installed theme plugins, including disabled ones and themes from other sources
- "Panel default" keeps the theme the admin enabled for the panel
- The selection is stored in the user's customization settings

## How it works

Every installed theme plugin (category `theme`) is registered on an empty panel to read its colors, fonts and css. These are then applied to the panel for users that selected the theme, and anything the panel's enabled theme changed that the selected theme doesn't is reset to the panel defaults.

Uninstall a theme to remove it from the selection.

## Limitations

- Only colors, fonts and css themes (`viteTheme`) are switched. Other changes a theme makes (e.g. render hooks) can't be undone per user.
- Themes whose css hasn't been built are not offered.
- The plugin replaces the profile page with an extended version, so it is not compatible with other plugins that replace the profile page.
