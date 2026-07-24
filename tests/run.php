<?php

declare(strict_types=1);

$testRoot = sys_get_temp_dir().'/log-viewer-tests-'.bin2hex(random_bytes(8));

define('LOG_VIEWER_TEST_STORAGE', $testRoot.'/storage');

if (! function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return LOG_VIEWER_TEST_STORAGE.($path ? DIRECTORY_SEPARATOR.$path : '');
    }
}

require dirname(__DIR__).'/vendor/autoload.php';

use LogViewer\Support\LogFile;

function assertTest(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function deleteTestDirectory(string $directory): void
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

try {
    mkdir(storage_path('logs'), 0777, true);

    $validLog = storage_path('logs/laravel.log');
    $outsideLog = $testRoot.'/outside.log';
    $nonLogFile = storage_path('logs/notes.txt');

    file_put_contents($validLog, "first line\nlast line\n");
    file_put_contents($outsideLog, "secret\n");
    file_put_contents($nonLogFile, "not a log\n");

    assertTest(LogFile::resolve($validLog) === realpath($validLog), 'Valid log was rejected.');
    assertTest(LogFile::resolve($outsideLog) === null, 'Log outside storage/logs was accepted.');
    assertTest(LogFile::resolve($nonLogFile) === null, 'Non-log file was accepted.');

    $linkedLog = storage_path('logs/linked.log');
    symlink($outsideLog, $linkedLog);
    assertTest(LogFile::resolve($linkedLog) === null, 'Symlink to an outside log was accepted.');

    $token = LogFile::token($validLog);
    assertTest(is_string($token), 'Valid log did not produce a token.');
    assertTest(LogFile::fromToken($token) === realpath($validLog), 'Token did not resolve to its log.');
    assertTest(LogFile::fromToken('../outside.log') === null, 'Invalid token was accepted.');

    $largeContent = str_repeat("old line\n", 400)."tail marker\n";
    file_put_contents($validLog, $largeContent);

    $tail = LogFile::readTail($validLog, 256);
    assertTest(str_contains($tail, 'Output truncated'), 'Truncated output was not labelled.');
    assertTest(str_ends_with($tail, "tail marker\n"), 'Tail did not contain the end of the log.');
    assertTest(strlen($tail) < 512, 'Tail exceeded the expected bounded payload.');

    assertTest(LogFile::clear($validLog), 'Valid log could not be cleared.');
    clearstatcache(true, $validLog);
    assertTest(filesize($validLog) === 0, 'Clear did not truncate the log.');
    assertTest(! LogFile::clear($outsideLog), 'Outside log could be cleared.');

    echo "LogViewer regression tests passed.\n";
} finally {
    deleteTestDirectory($testRoot);
}
