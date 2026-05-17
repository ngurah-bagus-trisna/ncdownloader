# NCDownloader

A unified download manager for Nextcloud — Aria2, yt-dlp, and cloud storage downloads all in one place.

## Features

- **Aria2 Web GUI** — HTTP, BitTorrent, Magnet links with full progress tracking (speed, ETA, percentage)
- **yt-dlp Integration** — Download videos from 700+ sites (YouTube, Twitter, Dailymotion, etc.)
- **Cloud Downloader** — Google Drive, OneDrive, SharePoint, Terabox and more
- **Torrent Search** — Built-in search across multiple torrent sites
- **Auto-Setup** — Bundled binaries auto-downloaded on first run, one-command setup script
- **Nextcloud-Native UI** — Dark mode support, follows Nextcloud design system

## Screenshots

<img width="800" alt="NCDownloader main UI" src="https://user-images.githubusercontent.com/3911975/142444998-54dd54a6-0c8e-4d49-8188-270964a99c50.png">
<img width="800" alt="NCDownloader settings" src="https://user-images.githubusercontent.com/3911975/142445020-27ec389a-5437-4d28-acc0-5e757fd6897d.png">

## Quick Start

### One-command setup

```bash
cd /var/www/nextcloud/apps/ncdownloader
./setup.sh
```

This installs all required tools (aria2, yt-dlp, ffmpeg, python3), PHP dependencies, and builds the frontend.

### Manual setup

```bash
# Install system tools
sudo apt install aria2 ffmpeg python3 python3-pip

# Install PHP deps
composer install

# Build frontend
npm install && npm run build

# Enable in Nextcloud
sudo -u www-data php occ app:enable ncdownloader
```

### Requirements

- Nextcloud 25–33
- PHP 8.0+
- Node 14+ & npm 7+ (for building)
- aria2c, yt-dlp (bundled binaries auto-downloaded if missing)

## Usage

### Aria2 Downloads
Paste any HTTP, Magnet, or torrent link → file downloads with full progress tracking.

### YouTube / Video Downloads
Select "Youtube-dl" tab → paste video URL → choose format (mp4, webm, m4a, mp3) → download.

### Cloud Downloads (Google Drive, OneDrive, etc.)
Select "Cloud" tab → paste share link → NCDownloader resolves the URL and downloads via aria2c. The app automatically handles Google Drive's virus scan confirmation page and extracts the correct filename.

Supported services:
- **Google Drive** — share links, `drive.google.com/file/d/*`, `drive.usercontent.google.com/download?*`
- **OneDrive / SharePoint** — share links and direct URLs
- **Dropbox** — via yt-dlp extractor
- **Terabox** and others via yt-dlp fallback

### Torrent Search
Select "Search Torrents" tab → enter keywords → pick a search site → browse and download results.

## Settings

### Admin Settings
- Aria2 RPC host, port, token
- Custom aria2c / yt-dlp binary paths
- Global Aria2 options
- Disable BT for non-admin users
- System info (binary versions, update checks)

### Personal Settings
- Download folder (per-user)
- Custom Aria2 options (per-user)
- Custom yt-dlp options (per-user)
- Hide error messages toggle

## Development

```bash
# Install deps
composer install
npm install

# Build frontend
npm run build

# Watch mode (hot rebuild)
npm run watch

# Lint
npm run lint
npm run stylelint

# Package for distribution
make dist
```

## License

AGPL v3. See [COPYING](COPYING).

## Links

- [Nextcloud App Store](https://apps.nextcloud.com/apps/ncdownloader)
- [GitHub Repository](https://github.com/shiningw/ncdownloader)
- [Issue Tracker](https://github.com/shiningw/ncdownloader/issues)
