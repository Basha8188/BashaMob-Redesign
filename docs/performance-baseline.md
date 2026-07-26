# Frontend performance baseline

Measured on 2026-07-26 from a clean working tree before the performance refactor.

## Reproducible commands

```powershell
npm run build
Get-ChildItem resources\css -Recurse -Filter *.css |
  Select-String -Pattern 'filter\s*:\s*blur\(|backdrop-filter\s*:|animation\s*:'
```

## Production asset baseline

| Asset | Raw size | Gzip size |
|---|---:|---:|
| `app.js` | 107.54 KB | 36.52 KB |
| `app.css` | 171.75 KB | 33.90 KB |
| `violet.css` | 79.78 KB | 14.27 KB |
| `admin.css` | 69.06 KB | 13.30 KB |
| `auth.css` | 25.25 KB | 6.41 KB |

The original layouts loaded the following raw CSS totals:

| Page type | CSS | JavaScript |
|---|---:|---:|
| Storefront | 251.53 KB | 107.54 KB |
| Authentication | 276.78 KB | 107.54 KB |
| Administration | 320.59 KB | 107.54 KB |

Source inspection found 172 `filter: blur(...)` declarations, 141 backdrop-filter declarations, 104 animation declarations, and 89 keyframe blocks. The active Aurora used four ribbons up to 190vmax on mobile, blur up to 105px, multiple blend modes, nine visual layers, and continuous animation.

`public/hot` was absent. Local configuration was `APP_ENV=local`, `APP_DEBUG=true`, and database-backed sessions. No production cache was enabled or changed.

## Measurement limitations

Browser FPS, paint, composited-layer, Lighthouse, and per-route Laravel response-time measurements were not recorded in this baseline because the local MySQL and application server were not running, and the connected browser runtime could not be initialized in the Windows sandbox. Those values are intentionally not estimated.
