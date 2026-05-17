<?php

namespace OCA\NCDownloader\Cloud;

use Symfony\Component\DomCrawler\Crawler;

class GDriveResolver
{
    private $crawler;
    private $cookieDir;

    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
        $this->cookieDir = sys_get_temp_dir();
    }

    /**
     * Resolve a Google Drive URL. Returns the direct download URL, the
     * original filename, and the session cookie so aria2c can pass
     * the virus scan confirmation.
     *
     * @return array{url: string, filename: string|null, cookie: string|null}
     */
    public function resolve(string $url): array
    {
        $fileId = $this->extractFileId($url);
        if (!$fileId) {
            return ['url' => $url, 'filename' => null, 'cookie' => null];
        }

        $directUrl = sprintf(
            'https://drive.usercontent.google.com/download?id=%s&export=download',
            $fileId
        );

        if (preg_match('/[?&]authuser=(\d+)/', $url, $m)) {
            $directUrl .= '&authuser=' . $m[1];
        }

        $filename = null;
        $cookie = null;

        try {
            $cookieJar = $this->cookieDir . '/gdrive-cookies-' . $fileId . '.txt';

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $directUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_COOKIEJAR => $cookieJar,
                CURLOPT_COOKIEFILE => $cookieJar,
            ]);

            $body = curl_exec($ch);
            curl_close($ch);

            // Extract cookie as a header string for aria2c
            $cookie = $this->extractCookie($cookieJar);

            if ($body && (strpos($body, 'uc-download-link') !== false || strpos($body, 'confirm') !== false)) {
                $this->crawler->clear();
                $this->crawler->addHtmlContent($body);

                $confirm = $this->crawler->filter('input[name="confirm"]')->count()
                    ? $this->crawler->filter('input[name="confirm"]')->attr('value') : null;
                $uuid = $this->crawler->filter('input[name="uuid"]')->count()
                    ? $this->crawler->filter('input[name="uuid"]')->attr('value') : null;

                if ($confirm) {
                    $directUrl .= '&confirm=' . urlencode($confirm);
                }
                if ($uuid) {
                    $directUrl .= '&uuid=' . urlencode($uuid);
                }

                // Extract the real filename from the confirmation page
                $nameNode = $this->crawler->filter('.uc-name-size a');
                if ($nameNode->count()) {
                    $filename = trim($nameNode->first()->text());
                }
            }
        } catch (\Exception $e) {
            \OCA\NCDownloader\Tools\Helper::debug('GDriveResolver: ' . $e->getMessage());
        }

        return ['url' => $directUrl, 'filename' => $filename, 'cookie' => $cookie];
    }

    private function extractCookie(string $cookieJar): ?string
    {
        if (!file_exists($cookieJar)) {
            return null;
        }

        $cookies = [];
        $lines = file($cookieJar, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip real comments (lines starting with '# ' or '# Netscape')
            if (strpos($line, '# ') === 0 || strpos($line, '#Netscape') === 0) {
                continue;
            }
            // Netscape cookie format:
            // domain  flag  path  secure  expiry  name  value
            // flag may start with #HttpOnly_
            $line = ltrim($line, '#');
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 7) {
                $cookies[] = $parts[5] . '=' . $parts[6];
            }
        }

        return !empty($cookies) ? implode('; ', $cookies) : null;
    }

    private function extractFileId(string $url): ?string
    {
        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/\/folders\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}
