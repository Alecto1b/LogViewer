<?php

namespace LogViewer\Pages;

use App\Facades\Plugin as PluginFacade;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use LogViewer\LogViewerPlugin;
use LogViewer\Support\LogFile;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class LogViewerPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Log Viewer';

    protected static ?string $slug = 'log-viewer';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'LogViewer::log-viewer';

    public ?string $logFile = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', \App\Models\Plugin::class) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->makeLogFileSelect(),
        ]);
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

    protected function downloadAction(): Action
    {
        return Action::make('download')
            ->icon('heroicon-c-arrow-down-tray')
            ->disabled(fn (): bool => ! $this->resolveLogFile())
            ->url(fn (): ?string => $this->getDownloadUrl());
    }

    protected function clearAction(): Action
    {
        return Action::make('clear')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->disabled(fn (): bool => ! $this->resolveLogFile())
            ->action(fn () => $this->clear());
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
}
