<?php

namespace LogViewer\Pages;

use App\Facades\Plugin as PluginFacade;
use App\Models\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use LogViewer\LogViewerPlugin;
use LogViewer\Support\LogFile;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

abstract class AbstractLogViewerPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Log Viewer';

    protected static ?string $slug = 'log-viewer';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $logFile = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(UserRole::Admin) ?? false;
    }

    public function refresh(): void
    {
        $this->dispatch('logContentUpdated', content: $this->read());
    }

    public function read(): string
    {
        return LogFile::readTail($this->logFile);
    }

    public function clear(): void
    {
        abort_unless(static::canAccess(), 403);

        if (LogFile::clear($this->logFile)) {
            $this->refresh();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $plugin = PluginFacade::getPlugin('LogViewer');

        return [
            'css' => $plugin->asset('index.css'),
            'js' => $plugin->asset('index.js'),
        ];
    }

    protected function makeLogFileSelect(): Select
    {
        return Select::make('logFile')
            ->hiddenLabel()
            ->placeholder('Select for a log file')
            ->lazy()
            ->searchable()
            ->options($this->getFileNames($this->getFinder()))
            ->afterStateUpdated(fn () => $this->refresh())
            ->suffixActions([
                $this->downloadAction(),
                $this->clearAction(),
            ]);
    }

    protected function getFileNames($files): Collection
    {
        return collect($files)
            ->reverse()
            ->mapWithKeys(fn (SplFileInfo $file): array => [
                $file->getRealPath() => $file->getFilename(),
            ]);
    }

    protected function getFinder(): Finder
    {
        return once(
            fn () => Finder::create()
                ->ignoreDotFiles(true)
                ->ignoreUnreadableDirs()
                ->files()
                ->sortByModifiedTime()
                ->in([storage_path('logs')])
                ->name('*.log'),
        );
    }

    protected function resolveLogFile(): ?string
    {
        if (! $logFile = LogFile::resolve($this->logFile)) {
            $this->logFile = null;

            return null;
        }

        return $logFile;
    }

    protected function getDownloadUrl(): ?string
    {
        if (! $token = LogFile::token($this->logFile)) {
            return null;
        }

        return URL::temporarySignedRoute(
            LogViewerPlugin::DOWNLOAD_ROUTE,
            now()->addMinutes(5),
            ['file' => $token],
        );
    }

    abstract protected function downloadAction();

    abstract protected function clearAction();
}
