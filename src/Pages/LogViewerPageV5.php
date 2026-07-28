<?php

namespace LogViewer\Pages;

use Filament\Actions\Action;
use Filament\Schemas\Schema;

class LogViewerPageV5 extends AbstractLogViewerPage
{
    protected string $view = 'LogViewer::log-viewer';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->makeLogFileSelect(),
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
}
