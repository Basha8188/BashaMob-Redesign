# BashaMob performance report

Measured on 2026-07-26 with `vite v7.3.6` production builds.

## Root causes confirmed

1. Every layout loaded one 107.54 KB JavaScript entry containing storefront, authentication, and all admin modules.
2. Authentication loaded `app.css`, `auth.css`, and `violet.css` for 276.78 KB raw CSS even though most of `app.css` and `components.css` was unrelated.
3. Aurora had four oversized ribbons, 12 dust elements, ten blur declarations, nine blend-mode declarations, and blur values up to 105px in active rules.
4. Repeated dashboard, product, category, and metric cards each applied backdrop filtering, creating nested glass compositing during scroll.
5. Product and repair images did not consistently reserve dimensions or use deferred decoding.
6. `public/hot` was not present, so a stale Vite development URL was not contributing to this measurement.

## Before and after assets

Raw production sizes:

| Page | Before CSS | After CSS | Change | Before JS | After JS | Change |
|---|---:|---:|---:|---:|---:|---:|
| Storefront | 251.53 KB | 252.61 KB | +1.08 KB | 107.54 KB | 73.90 KB | -31.3% |
| Authentication | 276.78 KB | 38.23 KB | -86.2% | 107.54 KB | 73.87 KB | -31.3% |
| Administration | 320.59 KB | 321.67 KB | +1.08 KB | 107.54 KB | 107.52 KB | effectively unchanged |

Authentication gzip CSS changed from 54.58 KB to 10.39 KB. Its four application asset requests now contain no admin JavaScript and no global `app.css`.

The small CSS increase on storefront/admin is the explicit performance override layer. It replaces active per-card backdrop filters with opaque-enough glass fills and adds safe rendering containment.

## Aurora and rendering changes

| Source characteristic | Before | After |
|---|---:|---:|
| Ribbon elements | 4 | 3 |
| Dust elements | 12 | 6 |
| Background blur declarations | 10 | 5 |
| Background blend-mode declarations | 9 | 0 |
| Maximum desktop active ribbon blur | 105px | 58px |
| Maximum mobile active ribbon blur | 105px+ | 34px |

Only transform and opacity are animated. Secondary mobile motion is disabled, low-memory/low-core/save-data devices receive a static reduced background, and all decorative animation pauses while the document is hidden.

Repeated dashboard and storefront cards no longer use backdrop filtering. Important top-level glass surfaces—navigation, primary panels, the login card, and modals—retain glass blur.

## Runtime checks

Using a local SQLite-backed Laravel server:

| Route | Observed result | Single request time |
|---|---:|---:|
| `/` | HTTP 200 | 656 ms |
| `/staff/login` | HTTP 200 | 266 ms |
| `/admin` as guest | HTTP 302 to login | 297 ms |

These are single local requests, not statistically representative benchmarks.

Browser FPS, paint time, main-thread time, Lighthouse, CLS, and composited-layer counts remain unmeasured because the connected browser runtime failed to start in the Windows sandbox. No values are inferred.

## Verification

```text
npm run build
PASS

php artisan test
51 tests passed
263 assertions
0 failures
```

Source-level asset tests verify:

- Storefront uses `storefront.js` and excludes admin/auth entry points.
- Authentication excludes `app.css` and admin JavaScript.
- Administration uses `admin.js`.

Production deployment and return-to-development commands are documented in `docs/performance-and-deployment.md`.

## Remaining risks

- `app.css` is still a legacy mixed storefront/admin stylesheet. Splitting it safely needs browser-based visual comparison across all module screens; this pass did not claim unused-CSS savings for those two areas.
- `app.js` still contains guarded shared, storefront, and authentication initializers. Admin-only modules are isolated, but further splitting can reduce storefront/auth JavaScript again.
- Cairo uses six externally hosted weights because all six weights are present in current computed source rules and no trusted local WOFF2 files exist in the repository.
- Real FPS and paint improvements must be confirmed on the target Android hardware using the repeatable profiling steps in the deployment document.
