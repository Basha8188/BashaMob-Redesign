# Fixed Premium Glass theme

The application uses the supplied artwork and one Purple / Lavender identity. Layout dimensions, responsive breakpoints, routes, controllers, models, database and operational behavior are unchanged.

## Files

- `public/images/theme/main-bg.png`: exact copy of `141654.png` (SHA-256 verified).
- `resources/css/theme/tokens.css`: central palette, silver primary text `#D8D4DA`, glass surfaces, borders, focus, popup and button tokens.
- `resources/css/theme/index.css`: loads tokens, compatibility aliases, the shared component layer and final Premium Glass rules; retired palette files are no longer imported.
- `resources/css/theme/system-unified.css`: existing component coverage, without light/dark color-scheme selection.
- `resources/css/theme/premium-glass.css`: visual-only application rules for backgrounds, shells, cards, forms, tables, dialogs, menus, notifications, public portals and auth. Existing print rules are retained.
- `resources/js/theme-manager.js`: fixed initializer and compatible exports, without preference storage, API calls, OS mode listeners or switching handlers.
- `resources/views/components/theme-bootstrap.blade.php`: fixed identity before painting and deployment-aware background URL.
- `resources/views/components/theme-picker.blade.php`: empty compatibility component for existing callers.
- `resources/views/components/navbar.blade.php`, `resources/views/components/admin/topbar.blade.php`, `resources/views/layouts/auth.blade.php`: removed theme controls.
- `resources/views/layouts/app.blade.php`: matching browser theme-color metadata.
- `resources/views/errors/minimal.blade.php`: Laravel error-page layout retained with shared theme assets, without OS-dependent colors.
- `tests/Feature/ThemePreferenceTest.php`: updated UI expectations to the fixed identity and verified saved account preferences cannot change it. Existing backend preference tests remain because the API/database contract was not changed.

## Verification

- Vite production build passed.
- Blade template compilation passed.
- Functional suite: 70 tests initially passed; the one failing new assertion concerned JSON-escaped slashes in the image URL. After correcting URL serialization, all 7 theme tests passed on rerun (67 assertions). The other 64 tests passed in the full run.
- Fixed-theme JavaScript initialization and compatibility calls passed for legacy light, dark and system inputs without storage, network or OS dependencies.
- Pint passed for the updated test; diff whitespace checks passed.
- In-app browser discovery returned no browsers. Interactive visual checks, mobile screenshots and manual popup inspection remain unverified.

Old account preference fields and their backend endpoint remain compatible but are not read or written by the UI. Dormant legacy CSS branches remain in the original stylesheets; the fixed `unified` identity cannot select them.
