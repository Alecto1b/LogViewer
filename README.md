# Log Viewer Plugin for Leconfe

## Description

View, download, and clear application log files directly from the Leconfe administration panel.

Only Leconfe administrators can access the viewer or its signed download endpoint. The editor displays at most the last 1 MiB of a log to keep Livewire responses bounded; full files remain available through the streamed download action.

## Compatibility

- Leconfe 1.5.x with Laravel 10, Filament 3, and PHP 8.1 or newer
- Leconfe 1.6.x with Laravel 13, Filament 5, and PHP 8.3 or newer

The same plugin ZIP automatically selects the matching Filament implementation at runtime. Filament 4 is not supported.

## Installation

Zip the `LogViewer` repository folder and upload the archive from Leconfe's Plugin Management page. The archive must contain a single top-level folder named `LogViewer`.

Composer is not required on the target server. The plugin can load its PHP classes directly from `src/`, while the compiled browser assets are already included in `public/`.

## Development

Install the frontend dependencies and compile the bundled assets:

```bash
npm install
npm run build
```

## Credit

This plugin is based on the [Filament Laravel Log](https://github.com/saade/filament-laravel-log) plugin by [Guilherme Saade](https://github.com/saade).
