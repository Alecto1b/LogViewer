<?php

namespace LogViewer\Support;

final class LogFile
{
    public const MAX_VIEW_BYTES = 1_048_576;

    public static function resolve(?string $path): ?string
    {
        if (! $path || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'log') {
            return null;
        }

        $logDirectory = realpath(storage_path('logs'));
        $logFile = realpath($path);

        if (
            ! $logDirectory ||
            ! $logFile ||
            ! is_file($logFile) ||
            ! is_readable($logFile) ||
            ! str_starts_with($logFile, $logDirectory.DIRECTORY_SEPARATOR)
        ) {
            return null;
        }

        return $logFile;
    }

    public static function token(?string $path): ?string
    {
        if (! $logFile = self::resolve($path)) {
            return null;
        }

        $relativePath = substr(
            $logFile,
            strlen(realpath(storage_path('logs')).DIRECTORY_SEPARATOR),
        );

        return rtrim(strtr(base64_encode($relativePath), '+/', '-_'), '=');
    }

    public static function fromToken(?string $token): ?string
    {
        if (! $token || ! preg_match('/^[A-Za-z0-9_-]+$/', $token)) {
            return null;
        }

        $padding = (4 - strlen($token) % 4) % 4;
        $relativePath = base64_decode(
            strtr($token, '-_', '+/').str_repeat('=', $padding),
            true,
        );

        if (! is_string($relativePath) || $relativePath === '') {
            return null;
        }

        return self::resolve(storage_path('logs').DIRECTORY_SEPARATOR.$relativePath);
    }

    public static function readTail(?string $path, int $maxBytes = self::MAX_VIEW_BYTES): string
    {
        if (! $logFile = self::resolve($path)) {
            return '';
        }

        $maxBytes = max(1, $maxBytes);
        $handle = @fopen($logFile, 'rb');

        if (! $handle) {
            return '';
        }

        try {
            $statistics = fstat($handle);
            $size = (int) ($statistics['size'] ?? 0);
            $offset = max(0, $size - $maxBytes);

            if ($offset > 0 && fseek($handle, $offset) !== 0) {
                return '';
            }

            $content = stream_get_contents($handle, $maxBytes);
        } finally {
            fclose($handle);
        }

        if (! is_string($content)) {
            return '';
        }

        $content = mb_scrub($content, 'UTF-8');

        if ($offset === 0) {
            return $content;
        }

        return sprintf(
            "[Output truncated to the last %s of %s bytes. Download the file for the complete log.]\n",
            number_format($maxBytes),
            number_format($size),
        ).$content;
    }

    public static function clear(?string $path): bool
    {
        if (! $logFile = self::resolve($path)) {
            return false;
        }

        $handle = @fopen($logFile, 'c');

        if (! $handle) {
            return false;
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                return false;
            }

            $cleared = ftruncate($handle, 0) && fflush($handle);
            flock($handle, LOCK_UN);

            return $cleared;
        } finally {
            fclose($handle);
        }
    }
}
