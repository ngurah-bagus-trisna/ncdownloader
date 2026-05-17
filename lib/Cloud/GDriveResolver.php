<?php

namespace OCA\NCDownloader\Cloud;

use OCA\NCDownloader\Http\Client;
use Symfony\Component\DomCrawler\Crawler;

class GDriveResolver
{
    private $client;
    private $crawler;

    public function __construct(Client $client, Crawler $crawler)
    {
        $this->client = $client;
        $this->crawler = $crawler;
    }

    /**
     * Resolve a Google Drive URL. Returns the direct download URL and the
     * original filename so aria2c can use proper --out filename.
     *
     * @return array{url: string, filename: string|null}
     */
    public function resolve(string $url): array
    {
        $fileId = $this->extractFileId($url);
        if (!$fileId) {
            return ['url' => $url, 'filename' => null];
        }

        $directUrl = sprintf(
            'https://drive.usercontent.google.com/download?id=%s&export=download',
            $fileId
        );

        if (preg_match('/[?&]authuser=(\d+)/', $url, $m)) {
            $directUrl .= '&authuser=' . $m[1];
        }

        $filename = null;

        try {
            $response = $this->client->request('GET', $directUrl, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ],
                'max_redirects' => 5,
            ]);

            $body = $response->getContent();

            // Check if this is a confirmation page (large files)
            if (strpos($body, 'uc-download-link') !== false || strpos($body, 'confirm') !== false) {
                $this->crawler->clear();
                $this->crawler->addHtmlContent($body);

                // Extract hidden form fields for the confirmation bypass
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

                // Extract the real filename from the confirmation page:
                // <span class="uc-name-size"><a href="...">filename.rar</a> (1.1G)</span>
                $nameNode = $this->crawler->filter('.uc-name-size a');
                if ($nameNode->count()) {
                    $filename = trim($nameNode->first()->text());
                }
            }
        } catch (\Exception $e) {
            \OCA\NCDownloader\Tools\Helper::debug('GDriveResolver: ' . $e->getMessage());
        }

        return ['url' => $directUrl, 'filename' => $filename];
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
