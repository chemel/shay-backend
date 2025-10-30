<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Symfony\Component\Serializer\Annotation\Groups;
use App\State\UserProvider;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\OpenApi\Model;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ApiResource(
    operations: [
        new Get(
            name: 'api_users_whoami',
            uriTemplate: '/users/whoami',
            paginationEnabled: false,
            provider: UserProvider::class,
            description: 'Retrieves the currently authenticated user',
            openapi: new Model\Operation(
                summary: 'Retrieves the current user information',
                description: 'Retrieves detailed information about the currently authenticated user.',
                responses: [
                    200 => new Model\Response(
                        description: 'User information retrieved successfully'
                    ),
                    401 => new Model\Response(
                        description: 'Authentication required'
                    )
                ]
            )
        )
    ],
    normalizationContext: ['groups' => ['user:read']]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[ApiProperty(
        description: 'The username of the user',
        example: 'john.doe'
    )]
    #[Groups(['user:read'])]
    private ?string $username = null;

    #[ORM\Column]
    #[ApiProperty(
        description: 'The roles of the user',
        example: ['ROLE_USER', 'ROLE_ADMIN'],
        openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'string',
                'enum' => ['ROLE_USER', 'ROLE_ADMIN']
            ]
        ]
    )]
    #[Groups(['user:read'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;
    
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
