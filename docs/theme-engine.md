# BashaMob Theme Engine

## Theme and mode separation

The engine keeps the established attributes compatible:

- `data-palette` controls the visual color family.
- `data-theme` remains the resolved `light` or `dark` mode used by legacy CSS.
- `data-mode` stores the selected `light`, `dark`, or `system` preference.
- `data-theme-name` stores the canonical persisted theme slug.

`default-violet` is internally rendered as the legacy `data-palette="violet"` selector. This deliberately preserves every existing Basha Violet selector and its exact values. The canonical database and LocalStorage value remains `default-violet`.

## Available themes

| Slug | Dark | Light |
|---|---|---|
| `default-violet` | Existing Basha Violet values | Existing Basha Violet values |
| `forest-moss` | `#27463B` | `#7FAE8B` |
| `cocoa-dust` | `#644B3E` | `#CDB6A1` |
| `twilight-blue` | `#203B5C` | `#84B6E4` |
| `moonstone` | `#6B737D` | `#D9E0E7` |

Each new theme defines steps 50–950 plus primary, surface, text, border, glass, shadow, gradient, and Aurora tokens.

## Compatibility

`resources/css/theme/compatibility.css` maps new theme tokens to the existing contracts, including:

- `--bm-*`
- `--bg-primary` and `--bg-secondary`
- `--violet-*` compatibility aliases
- `--glass-*`
- `--aurora-*`
- legacy aliases such as `--primary`, `--primary-color`, `--accent-color`, `--bg-color`, `--card-bg`, and `--sidebar-bg`

Semantic success, warning, danger, and informational colors are not replaced.

## Persistence

Migration `2026_07_26_000001_add_theme_preferences_to_users_table` adds:

- `theme_name`, default `default-violet`
- `theme_mode`, default `dark`
- `use_system_mode`, default `false`

Guests use LocalStorage. Authenticated users use LocalStorage for immediate rendering and asynchronously persist to `/theme-preference`. Unknown values fall back to `default-violet`. The old `basha-theme` light/dark key is retained and read; legacy `basha-palette=violet` is migrated logically without deleting it.

Theme changes happen without refresh and emit:

```js
window.addEventListener('bashamob:theme-changed', event => {
    // event.detail: { theme, mode, resolvedMode }
});
```

## Selector UI

The shared Blade picker is present in storefront navigation, authentication, administration, and signed public portal pages. It displays five visual circles only. Theme names exist only as accessibility labels and are not rendered beside the colors.

## Verification

- `npm run build`: passed.
- `php artisan migrate --force`: passed.
- `php artisan test`: 55 tests, 279 assertions, zero failures.
- Home and login HTML responses contain all five options and no visible theme-name text.
- Automated browser screenshots were unavailable because the connected browser runtime failed to initialize in the Windows sandbox.
