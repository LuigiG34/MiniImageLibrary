<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: 'users')]
#[ODM\Index(keys: ['email' => 1], options: ['unique' => true])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ODM\Id] 
    private ?string $id = null;

    #[Assert\NotBlank]
    #[Assert\Email(mode: 'strict')]
    #[Assert\Length(max: 180)]
    #[ODM\Field(type: 'string')]
    private string $email = '';

    #[Assert\NotBlank(groups: ['registration'])]
    #[Assert\Length(min: 8, max: 4096, groups: ['registration'])]
    #[ODM\Field(type: 'string')]
    private string $password = '';

    #[ODM\Field(type: 'collection')]
    private array $roles = ['ROLE_USER'];

    #[ODM\Field(type: 'date_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ODM\Field(type: 'date_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;





    #[ODM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable('now');
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->roles = $this->normalizeRoles($this->roles);
        $this->email = $this->normalizeEmail($this->email);
    }

    #[ODM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now');
        $this->roles = $this->normalizeRoles($this->roles);
        $this->email = $this->normalizeEmail($this->email);
    }






    public function getId(): ?string { 
        return $this->id; 
    }

    public function getEmail(): string { 
        return $this->email; 
    }

    public function setEmail(string $email): void { 
        $this->email = $this->normalizeEmail($email); 
    }

    public function getUserIdentifier(): string { 
        return $this->email; 
    }

    public function getUsername(): string { 
        return $this->email; 
    }

    public function getPassword(): string { 
        return $this->password; 
    }

    public function setPassword(string $hashedPassword): void { 
        $this->password = $hashedPassword; 
    }

    public function getRoles(): array { 
        return $this->roles; 
    }

    public function setRoles(array $roles): void { 
        $this->roles = $this->normalizeRoles($roles); 
    }

    public function addRole(string $role): void { 
        $this->roles = $this->normalizeRoles([...$this->roles, $role]); 
    }

    public function eraseCredentials(): void {}

    public function getCreatedAt(): ?\DateTimeImmutable { 
        return $this->createdAt; 
    }

    public function getUpdatedAt(): ?\DateTimeImmutable { 
        return $this->updatedAt; 
    }





    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function normalizeRoles(array $roles): array
    {
        $roles[] = 'ROLE_USER';
        $roles = array_map('strtoupper', array_map('trim', $roles));
        $roles = array_values(array_unique($roles));

        return $roles;
    }

    public function __toString(): string
    {
        return $this->email ?: 'user';
    }
}
