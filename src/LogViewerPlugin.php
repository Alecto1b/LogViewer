<?php

namespace LogViewer;

use App\Classes\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use LogViewer\Http\Controllers\DownloadLogController;
use LogViewer\Pages\LogViewerPage;

class LogViewerPlugin extends Plugin
{
    public const DOWNLOAD_ROUTE = 'log-viewer.download';

    protected bool $hasRegisteredRouteRebinding = false;

    public function boot()
    {
        $this->enablePublicAsset();
        $this->registerDownloadRoute();

        if (! $this->hasRegisteredRouteRebinding) {
            app()->rebinding('routes', fn () => $this->registerDownloadRoute());
            $this->hasRegisteredRouteRebinding = true;
        }
    }

    public function onPanel(Panel $panel): void
    {
        $panel->pages([LogViewerPage::class]);
    }

    public function getPluginPage(): ?string
    {
        if (! LogViewerPage::canAccess()) {
            return null;
        }

        try {
            return LogViewerPage::getUrl();
        } catch (\Throwable $th) {
            return null;
        }
    }

    protected function registerDownloadRoute(): void
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->getName() === static::DOWNLOAD_ROUTE) {
                return;
            }
        }

        Route::middleware(['web', 'auth', 'admin', 'signed'])
            ->get('/plugin/log-viewer/download', DownloadLogController::class)
            ->name(static::DOWNLOAD_ROUTE);
    }
}
