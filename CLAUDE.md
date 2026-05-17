# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

NCDownloader is a Nextcloud app that provides a web UI for **Aria2** (HTTP/BT/metalink downloads) and **yt-dlp** (video from 700+ sites). It bundles both binaries and manages them via PHP wrappers.

## Build & Development

```bash
# Install PHP dependencies
composer install

# Build frontend (outputs to js/ and css/)
npm run build

# Watch mode for frontend development
npm run watch

# Lint JS/Vue
npm run lint
npm run lint:fix

# Lint styles
npm run stylelint
npm run stylelint:fix

# Run tests (requires composer install first)
make test
# Or directly:
vendor/phpunit/phpunit/phpunit -c phpunit.xml          # unit tests
vendor/phpunit/phpunit/phpunit -c phpunit.integration.xml  # integration tests

# Package for distribution
make dist
```

Requirements: Node >= 14, npm >= 7, PHP >= 7.3. Webpack config is `webpack.app.js`.

## Architecture

### PHP Backend (Nextcloud AppFramework)

The app uses `IBootstrap` (`lib/AppInfo/Application.php`) — the modern Nextcloud bootstrapping pattern. Services are registered in `register()` and `boot()`.

**Controllers** (route handlers, `lib/Controller/`):
- `MainController` — renders the main UI (`Index`), handles direct Aria2 downloads and torrent uploads
- `Aria2Controller` — Aria2 RPC proxy (start/stop/pause/remove/status queries); called via POST to `/aria2/{path}`
- `YtdlController` — yt-dlp download management (start, list, delete, redownload)
- `SettingsController` — user and admin settings CRUD
- `SearchController` — torrent site search
- `ApiController` — REST API at `/api/v1/`

Routes are defined in `appinfo/routes.php`. Controllers use `@NoAdminRequired` and `@NoCSRFRequired` annotations.

**Core Services** (`lib/`):
- `Aria2/Aria2.php` — JSON-RPC client for aria2c. Manages the aria2 process lifecycle (start/stop), sends RPC calls via curl, filters/sorts responses. Configured via `Aria2/RunOptions.php`.
- `Ytdl/Ytdl.php` — yt-dlp wrapper using Symfony Process. Runs yt-dlp as a subprocess with async output handling via `Ytdl/Helper.php`.
- `Db/Settings.php` — Key-value settings store using Nextcloud's `IConfig` (user/system/app scopes).
- `Db/Helper.php` — Database queries on the `ncdownloader_info` table (tracks all downloads with gid, uid, type, status, filename).
- `Tools/Helper.php` — Static utility class (URL parsing, binary finding, Nextcloud server access, settings helpers, GitHub release fetching).
- `Tools/ExecutableFinder.php` — Forked from Symfony, finds binary paths on the system.

**Migrations**: `lib/Migration/` — creates the `ncdownloader_info` table.

**Settings UI**: `lib/Settings/` — Admin and Personal settings panels implementing `ISettings`.

**Search Sites**: `lib/Search/Sites/` — torrent site scrapers implementing `searchInterface`, auto-discovered by `Helper::findSearchSites()`.

### Frontend (Vue 3 + Webpack)

Two entry points in `webpack.app.js`:
- `src/index.js` → `js/app.js` — the main downloader UI
- `src/settings.js` → `js/appSettings.js` — admin/personal settings page

Templates are PHP files in `templates/`: `Index.php` (main layout with Navigation + Content includes), `Content.php`, `Navigation.php`, and settings templates in `templates/settings/`.

The main Vue app (`src/App.vue`) mounts at `#ncdownloader-form-wrapper` and injects settings from `#app-settings-data` (data attributes populated by PHP).

Frontend structure:
- `src/components/` — Vue SFCs (mainForm, settingsRow, toggleButton, etc.)
- `src/lib/` — TypeScript modules (http.ts, polling.ts, contentTable.ts, eventHandler.ts, etc.)
- `src/actions/` — buttonActions.js (download/search/upload), updatePage.js (polling/table refresh)
- `src/utils/` — helper.js, aria2Options.js, ytdlOptions.js
- `src/css/` — SCSS stylesheets

jQuery is used alongside Vue 3 for DOM manipulation and AJAX. `OC` (Nextcloud JS global) is an external in webpack.

### Data Flow

1. User submits URL → Vue `App.vue` calls PHP controller via AJAX
2. PHP controller delegates to `Aria2` or `Ytdl` service
3. Download metadata is persisted in `ncdownloader_info` DB table
4. Frontend polls `/status/{active|waiting|complete|fail}` for aria2 or `/ytdl/get` for yt-dlp
5. Aria2 uses shell hooks (`hooks/`) to call `occ aria2 complete` on download finish

### Key Dependencies

- **PHP**: symfony/dom-crawler, symfony/http-client, symfony/css-selector (for torrent scraping)
- **JS**: vue 3, vuex, webpack 5, bootstrap 5, jquery, tippy.js (@nextcloud/* packages are devDependencies bundled into the build)
- **Binaries**: aria2c and yt-dlp are bundled at `bin/` (fetched from https://github.com/shiningw/ncdownloader-bin if not present)
