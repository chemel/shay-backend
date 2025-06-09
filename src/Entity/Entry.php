<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Repository\EntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Routing\Requirement\Requirement;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\OpenApi\Model;

#[ORM\Entity(repositoryClass: EntryRepository::class)]
#[ORM\HasLifecycleCallbacks()]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/entries',
            description: 'Retrieves the collection of RSS Feed entries',
            openapi: new Model\Operation(
                summary: 'Gets all RSS feed entries',
                description: 'Retrieves the collection of RSS Feed entries ordered by date',
                responses: [
                    '200' => [
                        'description' => 'RSS Feed entries collection retrieved successfully'
                    ]
                ]
            )
        ),
        new Get(
            uriTemplate: '/entries/{id}',
            requirements: ['id' => Requirement::UUID_V6],
            description: 'Retrieves a RSS Feed entry',
            openapi: new Model\Operation(
                summary: 'Gets a RSS feed entry',
                description: 'Retrieves a specific RSS Feed entry by its UUID',
                responses: [
                    '200' => [
                        'description' => 'RSS Feed entry retrieved successfully'
                    ],
                    '404' => [
                        'description' => 'RSS Feed entry not found'
                    ]
                ]
            )
        ),
        new Patch(
            uriTemplate: '/entries/{id}',
            requirements: ['id' => Requirement::UUID_V6],
            description: 'Updates a RSS Feed entry (partial)',
            openapi: new Model\Operation(
                summary: 'Updates the read status of an entry',
                description: 'Updates a RSS Feed entry, currently only supports updating the read status',
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'readed' => [
                                        'type' => 'boolean',
                                        'description' => 'Whether the entry has been read',
                                        'example' => true
                                    ]
                                ]
                            ]
                        ]
                    ])
                ),
                responses: [
                    '200' => [
                        'description' => 'RSS Feed entry updated successfully'
                    ],
                    '400' => [
                        'description' => 'Invalid input'
                    ],
                    '404' => [
                        'description' => 'RSS Feed entry not found'
                    ]
                ]
            )
        ),
    ],
    order: ['date' => 'DESC'],
    normalizationContext: [
        'skip_null_values' => false,
        'groups' => ['entry:read']
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'feed.id' => 'exact',
    'feed.category.id' => 'exact'
])]
class Entry
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups('entry:read')]
    #[ApiProperty(description: 'The unique identifier of the RSS Feed entry')]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups('entry:read')]
    #[ApiProperty(
        description: 'The publication date of the entry',
        example: '2024-03-21T15:30:00+00:00'
    )]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups('entry:read')]
    #[ApiProperty(
        description: 'The permanent URL of the entry',
        example: 'https://example.com/blog/post-1'
    )]
    private ?string $permalink = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups('entry:read')]
    #[ApiProperty(
        description: 'The title of the entry',
        example: 'My First Blog Post'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('entry:read')]
    #[ApiProperty(
        description: 'The content of the entry',
        example: 'This is the content of my first blog post...'
    )]
    private ?string $content = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[ApiProperty(
        description: 'The hash of the entry content for deduplication',
        readable: false
    )]
    private ?string $hash = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups('entry:read')]
    #[ApiProperty(
        description: 'Whether the entry has been read',
        example: false,
        default: false
    )]
    private bool $readed = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ApiProperty(
        description: 'When the entry was created in the system',
        example: '2024-03-21T15:30:00+00:00'
    )]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Feed::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(
        description: 'The RSS Feed this entry belongs to',
        required: true
    )]
    private ?Feed $feed = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getPermalink(): ?string
    {
        return $this->permalink;
    }

    public function setPermalink(string $permalink): self
    {
        $this->permalink = $permalink;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getReaded(): ?bool
    {
        return $this->readed;
    }

    public function setReaded(bool $readed): self
    {
        $this->readed = $readed;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFeed(): ?Feed
    {
        return $this->feed;
    }

    public function setFeed(?Feed $feed): self
    {
        $this->feed = $feed;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }
}
