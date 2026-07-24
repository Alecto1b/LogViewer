<?php

namespace LogViewer\Pages;

use App\Facades\Plugin as PluginFacade;
use App\Models\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
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

class LogViewerPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    protected static ?string $title = 'Log Viewer';

    protected string $view = 'LogViewer::log-viewer';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $logFile = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('logFile')
                ->label(false)
                ->placeholder('Select for a log file')
                ->lazy()
                ->searchable()
                ->options($this->getFileNames($this->getFinder()))
                ->afterStateUpdated(fn () => $this->refresh())
                ->suffixActions([
                    $this->downloadAction(),
                    $this->clearAction(),
                ]),
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(UserRole::Admin) ?? false;
    }

    public function refresh(): void
    {
        $this->dispatch('logContentUpdated', content: $this->read());
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

    public function downloadAction(): Action
    {
        return Action::make('download')
            ->icon('heroicon-c-arrow-down-tray')
            ->disabled(fn (): bool => ! $this->resolveLogFile())
            ->url(fn (): ?string => $this->getDownloadUrl());
    }

    public function clearAction(): Action
    {
        return Action::make('clear')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->disabled(fn (): bool => ! $this->resolveLogFile())
            ->action(fn () => $this->clear());
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
