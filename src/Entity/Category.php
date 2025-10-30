<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Routing\Requirement\Requirement;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\OpenApi\Model;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/categories',
            description: 'Retrieves the collection of RSS Feed categories',
            openapi: new Model\Operation(
                summary: 'Gets all categories',
                description: 'Retrieves the collection of RSS Feed categories ordered by name',
                responses: [
                    200 => new Model\Response(
                        description: 'Categories collection retrieved successfully'
                    )
                ]
            )
        ),
        new Post(
            uriTemplate: '/categories',
            denormalizationContext: ['groups' => ['category:write']],
            description: 'Creates a new category',
            openapi: new Model\Operation(
                summary: 'Creates a new category',
                description: 'Creates a new category for organizing RSS Feeds',
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => [
                                        'type' => 'string',
                                        'description' => 'The name of the category',
                                        'example' => 'Technology',
                                        'minLength' => 2,
                                        'maxLength' => 255
                                    ]
                                ],
                                'required' => ['name']
                            ]
                        ]
                    ])
                ),
                responses: [
                    201 => new Model\Response(
                        description: 'Category created successfully'
                    ),
                    400 => new Model\Response(
                        description: 'Invalid input'
                    ),
                    422 => new Model\Response(
                        description: 'Unprocessable entity (validation failed)'
                    )
                ]
            )
        ),
        new Delete(
            uriTemplate: '/categories/{id}',
            requirements: ['id' => Requirement::UUID_V6],
            description: 'Removes a category',
            openapi: new Model\Operation(
                summary: 'Deletes a category',
                description: 'Deletes a category and all its associated feeds',
                responses: [
                    204 => new Model\Response(
                        description: 'Category deleted successfully'
                    ),
                    404 => new Model\Response(
                        description: 'Category not found'
                    ),
                    409 => new Model\Response(
                        description: 'Conflict: Category still has associated feeds'
                    )
                ]
            )
        )
    ],
    order: ['name' => 'ASC'],
    paginationEnabled: false,
    normalizationContext: ['groups' => ['category:read']]
)]
#[UniqueEntity('name', message: "The name must be unique")]
class Category
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups('category:read')]
    #[ApiProperty(
        description: 'The unique identifier of the category',
        example: '01234567-89ab-cdef-0123-456789abcdef'
    )]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Groups(['category:read', 'category:write'])]
    #[Assert\NotBlank(message: "The name cannot be empty")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "The name must be at least {{ limit }} characters long",
        maxMessage: "The name cannot be longer than {{ limit }} characters"
    )]
    #[ApiProperty(
        description: 'The name of the category',
        example: 'Technology',
        required: true
    )]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Feed::class)]
    #[ApiProperty(
        description: 'The RSS feeds in this category',
        readable: false,
        writable: false
    )]
    private Collection $feeds;
    
    public function __construct()
    {
        $this->feeds = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Feed>
     */
    public function getFeeds(): Collection
    {
        return $this->feeds;
    }

    public function addFeed(Feed $feed): self
    {
        if (!$this->feeds->contains($feed)) {
            $this->feeds[] = $feed;
            $feed->setCategory($this);
        }

        return $this;
    }

    public function removeFeed(Feed $feed): self
    {
        if ($this->feeds->removeElement($feed)) {
            // set the owning side to null (unless already changed)
            if ($feed->getCategory() === $this) {
                $feed->setCategory(null);
            }
        }

        return $this;
    }
}
