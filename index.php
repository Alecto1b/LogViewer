<?php

use LogViewer\LogViewerPlugin;

if (! class_exists(LogViewerPlugin::class)) {
    $composerAutoloader = __DIR__.'/vendor/autoload.php';

    if (is_file($composerAutoloader)) {
        require $composerAutoloader;
    } else {
        spl_autoload_register(static function (string $class): void {
            $prefix = 'LogViewer\\';

            if (! str_starts_with($class, $prefix)) {
                return;
            }

            $relativeClass = substr($class, strlen($prefix));
            $file = __DIR__.'/src/'.str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass).'.php';

            if (is_file($file)) {
                require $file;
            }
        });
    }
}

return new LogViewerPlugin;
