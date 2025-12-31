<?php

namespace Espo\Custom\EntryPoints;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\EntryPoint\Traits\NoAuth;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Entities\Attachment;

class ConnectraFavicon implements EntryPoint
{
    use NoAuth;

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private FileStorageManager $fileStorageManager
    ) {}

    public function run(Request $request, Response $response): void
    {
        $faviconId = $this->config->get('flavourFaviconId');

        if (!$faviconId) {
            $this->serveDefaultFavicon($response);
            return;
        }

        $attachment = $this->entityManager
            ->getRDBRepositoryByClass(Attachment::class)
            ->getById($faviconId);

        if (!$attachment) {
            $this->serveDefaultFavicon($response);
            return;
        }

        $stream = $this->fileStorageManager->getStream($attachment);
        $fileSize = $stream->getSize() ?? $this->fileStorageManager->getSize($attachment);

        $mimeType = $attachment->getType() ?? 'image/svg+xml';

        $response->setHeader('Content-Type', $mimeType);
        $response->setHeader('Content-Length', (string) $fileSize);
        $response->setHeader('Cache-Control', 'public, max-age=86400');
        $response->setBody($stream);
    }

    private function serveDefaultFavicon(Response $response): void
    {
        $defaultPath = 'client/img/favicon.svg';

        if (!file_exists($defaultPath)) {
            throw new NotFound("Default favicon not found.");
        }

        $content = file_get_contents($defaultPath);
        $response->setHeader('Content-Type', 'image/svg+xml');
        $response->setHeader('Content-Length', (string) strlen($content));
        $response->setHeader('Cache-Control', 'public, max-age=86400');
        $response->writeBody($content);
    }
}
