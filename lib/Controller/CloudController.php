<?php

namespace OCA\NCDownloader\Controller;

use OCA\NCDownloader\Aria2\Aria2;
use OCA\NCDownloader\Cloud\GDriveResolver;
use OCA\NCDownloader\Db\Helper as DbHelper;
use OCA\NCDownloader\Tools\Helper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;

class CloudController extends Controller
{
    private $uid;
    private $l10n;
    private $aria2;
    private $resolver;
    private $dbconn;
    private $downloadDir;

    public function __construct(
        $appName,
        IRequest $request,
        $UserId,
        IL10N $IL10N,
        Aria2 $aria2,
        GDriveResolver $resolver
    ) {
        parent::__construct($appName, $request);
        $this->uid = $UserId;
        $this->l10n = $IL10N;
        $this->aria2 = $aria2;
        $this->aria2->init();
        $this->resolver = $resolver;
        $this->dbconn = new DbHelper();
        $this->downloadDir = Helper::getDownloadDir();
    }

    /**
     * @NoAdminRequired
     */
    public function Download(string $url): JSONResponse
    {
        $url = trim($url);

        // Resolve GDrive URLs to get past the virus scan confirmation page
        $resolvedFilename = null;
        $resolvedCookie = null;
        if (Helper::isGDriveUrl($url)) {
            $resolved = $this->resolver->resolve($url);
            $url = $resolved['url'];
            $resolvedFilename = $resolved['filename'];
            $resolvedCookie = $resolved['cookie'];
        }

        $dlDir = $this->aria2->getDownloadDir();
        if (!is_writable($dlDir)) {
            return new JSONResponse(['error' => sprintf('%s is not writable', $dlDir)]);
        }

        $resp = $this->_download($url, $resolvedFilename, $resolvedCookie);
        return new JSONResponse($resp);
    }

    private function _download(string $url, ?string $resolvedFilename = null, ?string $cookie = null): array
    {
        // Pass the GDrive session cookie to aria2c so confirm tokens are valid
        if ($cookie) {
            $this->aria2->setOption('header', ['Cookie: ' . $cookie]);
        }

        $filename = $resolvedFilename ?: Helper::getFilename($url);
        if ($filename && $filename !== 'unknown' && $filename !== 'download') {
            $this->aria2->setFileName($filename);
        }

        $result = $this->aria2->download($url);

        // Clean up: remove the cookie header so it doesn't leak to future downloads
        if ($cookie) {
            $this->aria2->setOption('header', null);
        }

        if (!$result) {
            return ['error' => 'Failed to start download'];
        }
        if (isset($result['error'])) {
            return $result;
        }

        $data = [
            'uid' => $this->uid,
            'gid' => $result,
            'type' => Helper::DOWNLOADTYPE['ARIA2'],
            'filename' => $filename ?: 'unknown',
            'timestamp' => time(),
            'data' => serialize(['link' => $url, 'path' => Helper::getDownloadDir()]),
        ];
        $this->dbconn->save($data);

        return ['message' => $filename, 'result' => $result, 'file' => $filename];
    }

    /**
     * @NoAdminRequired
     */
    public function Status(): JSONResponse
    {
        // Cloud downloads use aria2, so they appear in the existing
        // Active/Waiting/Complete/Failed queues via Aria2Controller::getStatus()
        return new JSONResponse(['title' => [], 'row' => []]);
    }
}
