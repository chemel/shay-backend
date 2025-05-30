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

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/categories'),
        new Post(
            uriTemplate: '/categories',
            denormalizationContext: ['groups' => ['category:write']]
        ),
        new Delete(uriTemplate: '/categories/{id}')
    ],
    order: ['name' => 'ASC'],
    paginationEnabled: false,
    normalizationContext: ['groups' => ['read']]
)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups('read')]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['read', 'category:write'])]
    #[Assert\NotBlank(message: "The name cannot be empty")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "The name must be at least {{ limit }} characters long",
        maxMessage: "The name cannot be longer than {{ limit }} characters"
    )]
    private $name;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Feed::class)]
    private $feeds;

    public function __construct()
    {
        $this->feeds = new ArrayCollection();
    }

    public function getId(): ?int
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
