<?php

namespace LogViewer\Pages;

use Filament\Schemas\Schema;

if (class_exists(Schema::class)) {
    class LogViewerPage extends LogViewerPageV5 {}
} else {
    class LogViewerPage extends LogViewerPageV3 {}
}
