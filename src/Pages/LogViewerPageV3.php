<?php

namespace LogViewer\Pages;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;

class LogViewerPageV3 extends AbstractLogViewerPage
{
    protected static string $view = 'LogViewer::log-viewer';

    public function form(Form $form): Form
    {
        return $form->schema([
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
