<?php

declare(strict_types=1);

namespace App\Classes {
    abstract class Plugin
    {
    }
}

namespace {
    function assertVendorlessTest(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new RuntimeException($message);
        }
    }

    function copyVendorlessDirectory(string $source, string $destination): void
    {
        mkdir($destination, 0777, true);

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($items as $item) {
            $target = $destination.DIRECTORY_SEPARATOR.$items->getSubPathName();

            if ($item->isDir()) {
                mkdir($target, 0777, true);
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    function deleteVendorlessDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() && ! $item->isLink()
                ? rmdir($item->getPathname())
                : unlink($item->getPathname());
        }

        rmdir($directory);
    }

    $pluginRoot = dirname(__DIR__);
    $testRoot = sys_get_temp_dir().'/log-viewer-vendorless-'.bin2hex(random_bytes(8));

    try {
        mkdir($testRoot, 0777, true);
        copy($pluginRoot.'/index.php', $testRoot.'/index.php');
        copyVendorlessDirectory($pluginRoot.'/src', $testRoot.'/src');

        assertVendorlessTest(
            ! file_exists($testRoot.'/vendor/autoload.php'),
            'Vendorless fixture unexpectedly contains Composer autoload.',
        );

        $plugin = require $testRoot.'/index.php';

        assertVendorlessTest(
            $plugin instanceof \LogViewer\LogViewerPlugin,
            'Vendorless index.php did not return LogViewerPlugin.',
        );
        assertVendorlessTest(
            class_exists(\LogViewer\Support\LogFile::class),
            'Fallback autoloader did not load a nested LogViewer class.',
        );

        echo "LogViewer vendorless bootstrap test passed.\n";
    } finally {
        deleteVendorlessDirectory($testRoot);
    }
}
