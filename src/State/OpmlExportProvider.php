<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\OpmlService;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the OPML export of the feeds.
 *
 * It returns a Response, which API Platform sends as is, without serializing it.
 *
 * @implements ProviderInterface<Response>
 */
class OpmlExportProvider implements ProviderInterface
{
    public function __construct(
        private readonly OpmlService $opmlService,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        return new Response($this->opmlService->export(), Response::HTTP_OK, [
            'Content-Type' => 'text/x-opml; charset=utf-8',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'feeds.opml'),
        ]);
    }
}
