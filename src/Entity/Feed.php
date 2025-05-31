<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use App\Repository\FeedRepository;
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

#[ORM\Entity(repositoryClass: FeedRepository::class)]
#[ORM\HasLifecycleCallbacks()]
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/feeds'),
        new Post(uriTemplate: '/feeds'),
        new Delete(
            uriTemplate: '/feeds/{id}',
            requirements: ['id' => Requirement::UUID_V6]
        )
    ],
    order: ['title' => 'ASC'],
    paginationEnabled: false,
    normalizationContext: ['groups' => ['read']],
)]
#[UniqueEntity('url', message: "The URL must be unique")]
class Feed
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups('read')]
    private ?Uuid $id;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Groups('read')]
    #[Assert\Url(message: "The URL '{{ value }}' is not a valid URL")]
    #[Assert\NotBlank(message: "The URL cannot be empty")]
    private $url;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups('read')]
    private $title;

    #[ORM\Column(type: Types::BOOLEAN)]
    private $enabled = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 5])]
    private $fetchEvery = 5;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fetchAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fetchedAt = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private $errorCount = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private $errorMessage;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 30])]
    private $purge = 30;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'feed', targetEntity: Entry::class, orphanRemoval: true)]
    private $entries;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'feeds')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('read')]
    private $category;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }
    
    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function getFetchEvery(): ?int
    {
        return $this->fetchEvery;
    }

    public function setFetchEvery(int $fetchEvery): static
    {
        $this->fetchEvery = $fetchEvery;

        return $this;
    }

    public function getFetchAt(): ?\DateTimeInterface
    {
        return $this->fetchAt;
    }

    public function setFetchAt(?\DateTimeInterface $fetchAt): static
    {
        $this->fetchAt = $fetchAt;

        return $this;
    }

    public function getFetchedAt(): ?\DateTimeInterface
    {
        return $this->fetchedAt;
    }

    public function setFetchedAt(?\DateTimeInterface $fetchedAt): self
    {
        $this->fetchedAt = $fetchedAt;

        return $this;
    }

    public function getErrorCount(): ?int
    {
        return $this->errorCount;
    }

    public function setErrorCount(int $errorCount): self
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    public function incrementErrorCount(): self
    {
        ++$this->errorCount;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getPurge(): ?int
    {
        return $this->purge;
    }

    public function getPurgeDate(): \DateTimeInterface
    {
        $purgeDate = new \DateTime();
        $purgeDate->modify('-'.$this->getPurge().' days');

        return $purgeDate;
    }

    public function setPurge(int $purge): static
    {
        $this->purge = $purge;

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

    /**
     * @return Collection<int, Entry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(Entry $entry): self
    {
        if (!$this->entries->contains($entry)) {
            $this->entries[] = $entry;
            $entry->setFeed($this);
        }

        return $this;
    }

    public function removeEntry(Entry $entry): self
    {
        if ($this->entries->removeElement($entry)) {
            // set the owning side to null (unless already changed)
            if ($entry->getFeed() === $this) {
                $entry->setFeed(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        if (null === $this->getTitle()) {
            $this->setTitle($this->getUrl());
        }
    }
}
