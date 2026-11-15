<?php

namespace App\State;

use Alc\Opml\Exception\InvalidOpmlException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Opml;
use App\Service\OpmlService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Imports the OPML file uploaded in the "file" field of a multipart/form-data request.
 *
 * @implements ProcessorInterface<mixed, Opml>
 */
class OpmlImportProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OpmlService $opmlService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Opml
    {
        $request = $context['request'] ?? null;
        $file = $request instanceof Request ? $request->files->get('file') : null;

        if (!$file instanceof UploadedFile) {
            throw new BadRequestHttpException('The "file" field is required.');
        }

        if (!$file->isValid()) {
            throw new BadRequestHttpException($file->getErrorMessage());
        }

        try {
            $imported = $this->opmlService->import($file->getPathname());
        } catch (InvalidOpmlException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        return new Opml($imported);
    }
}
