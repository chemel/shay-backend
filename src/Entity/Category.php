<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/categories'),
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
    #[Groups('read')]
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
