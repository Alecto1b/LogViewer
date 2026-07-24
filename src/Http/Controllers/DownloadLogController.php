<?php

namespace LogViewer\Http\Controllers;

use Illuminate\Http\Request;
use LogViewer\Support\LogFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadLogController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $token = $request->query('file');
        $logFile = LogFile::fromToken(is_string($token) ? $token : null);

        abort_unless($logFile, 404);

        return response()->download(
            $logFile,
            basename($logFile),
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
