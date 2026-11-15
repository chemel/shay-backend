<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\State\OpmlExportProvider;
use App\State\OpmlImportProcessor;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Exposes the OPML import and export of the feeds and their categories.
 */
#[ApiResource(
    shortName: 'Opml',
    operations: [
        new Get(
            name: 'api_opml_export',
            uriTemplate: '/opml/export',
            outputFormats: ['opml' => ['text/x-opml', 'application/xml', 'text/xml']],
            provider: OpmlExportProvider::class,
            description: 'Exports the feeds as an OPML file',
            openapi: new Model\Operation(
                summary: 'Exports the feeds as an OPML file',
                description: 'Exports all the categories and their RSS Feeds as an OPML 1.0 document, sent as a file attachment.',
                responses: [
                    200 => new Model\Response(
                        description: 'OPML document',
                        content: new \ArrayObject([
                            'text/x-opml' => [
                                'schema' => [
                                    'type' => 'string',
                                    'format' => 'binary',
                                ],
                            ],
                        ])
                    ),
                    401 => new Model\Response(
                        description: 'Authentication required'
                    ),
                ]
            )
        ),
        new Post(
            name: 'api_opml_import',
            uriTemplate: '/opml/import',
            status: 200,
            inputFormats: ['multipart' => ['multipart/form-data']],
            deserialize: false,
            validate: false,
            processor: OpmlImportProcessor::class,
            description: 'Imports the feeds of an OPML file',
            openapi: new Model\Operation(
                summary: 'Imports the feeds of an OPML file',
                description: 'Imports the categories and the RSS Feeds of an uploaded OPML file. Feeds whose URL already exists and feeds without category are skipped.',
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                        'description' => 'The OPML file to import',
                                    ],
                                ],
                                'required' => ['file'],
                            ],
                        ],
                    ])
                ),
                responses: [
                    200 => new Model\Response(
                        description: 'OPML file imported successfully'
                    ),
                    400 => new Model\Response(
                        description: 'Missing or failed file upload'
                    ),
                    401 => new Model\Response(
                        description: 'Authentication required'
                    ),
                    422 => new Model\Response(
                        description: 'The file is not a valid OPML document'
                    ),
                ]
            )
        ),
    ],
    normalizationContext: ['groups' => ['opml:read']]
)]
class Opml
{
    #[ApiProperty(
        description: 'The number of feeds created by the import',
        example: 12
    )]
    #[Groups('opml:read')]
    public int $imported = 0;

    public function __construct(int $imported = 0)
    {
        $this->imported = $imported;
    }
}
