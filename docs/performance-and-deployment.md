# Performance and deployment

## Asset ownership

- Storefront: `app.css`, `violet.css`, `storefront.js`.
- Authentication: `auth.css`, `assistant.css`, `auth-theme.css`, `auth.js`.
- Administration: `app.css`, `admin.css`, `violet.css`, `admin.js`.
- `admin.js` is the only entry that imports `admin-i18n.js`, `admin-search.js`, and `admin-crud.js`.
- `auth-theme.css` contains only the tokens, reset, and optimized Aurora required by authentication.

## Development

Keep local development settings:

```env
APP_ENV=local
APP_DEBUG=true
```

Run either the existing XAMPP workflow or:

```bash
php artisan optimize:clear
npm run dev
php artisan serve
```

If `public/hot` exists while the Vite development server is not running, Laravel will still try to load assets from that server. Start `npm run dev` or remove the stale file only after confirming the Vite process is stopped.

## Production

Use production environment values without committing secrets:

```env
APP_ENV=production
APP_DEBUG=false
```

Then deploy with:

```bash
npm ci
npm run build
php artisan optimize
```

For queue-backed functionality, run the configured queue worker and scheduler through the hosting process manager:

```bash
php artisan queue:work --tries=3
php artisan schedule:work
```

Return a production checkout to development mode with:

```bash
php artisan optimize:clear
npm run dev
```

## Browser profiling

For repeatable manual validation, record Chrome DevTools Performance traces for `/`, `/staff/login`, and `/admin` at desktop and a mid-range Android viewport. Record a 10-second idle Aurora trace and a 10-second continuous scroll trace, with screenshots, Web Vitals, rendering, painting, scripting, layer count, console errors, and network transfer sizes. Repeat with identical throttling after each visual change.
