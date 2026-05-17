<?php

namespace OCA\NCDownloader\Cloud;

use Symfony\Component\Process\Process;

class GDriveResolver
{
    private $scriptPath;

    public function __construct()
    {
        $this->scriptPath = __DIR__ . '/../../bin/gdrive-resolve.py';
    }

    /**
     * Resolve a Google Drive URL to a direct download link using Python
     * session-based resolution (handles cookies + confirmation form natively).
     *
     * @return array{url: string, filename: string|null}
     */
    public function resolve(string $url): array
    {
        if (!file_exists($this->scriptPath)) {
            return ['url' => $url, 'filename' => null];
        }

        $python = \OCA\NCDownloader\Tools\Helper::findBinaryPath('python3') ?: '/usr/bin/python3';

        try {
            $process = new Process([$python, $this->scriptPath, $url]);
            $process->setTimeout(60);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $data = json_decode($output, true);

                if ($data && isset($data['url'])) {
                    if (isset($data['error'])) {
                        return ['url' => $url, 'filename' => $data['error'], 'error' => $data['error']];
                    }
                    return [
                        'url' => $data['url'],
                        'filename' => $data['filename'] ?? null,
                    ];
                }
            }

            $error = $process->getErrorOutput() ?: $process->getOutput();
            \OCA\NCDownloader\Tools\Helper::debug('GDriveResolver failed: ' . $error);
        } catch (\Exception $e) {
            \OCA\NCDownloader\Tools\Helper::debug('GDriveResolver exception: ' . $e->getMessage());
        }

        return ['url' => $url, 'filename' => null];
    }
}
