<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\EmbeddedDocument]
final class ImageMetadata
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    #[ODM\Field(type: 'string')]
    private string $title = '';

    #[ODM\Field(type: 'collection')]
    private array $tags = [];

    #[Assert\NotBlank]
    #[ODM\Field(type: 'string')]
    private string $mime = 'application/octet-stream';

    #[ODM\ReferenceOne(targetDocument: User::class, storeAs: 'id', nullable: true)]
    private ?User $owner = null;


    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = trim($title); }

    public function getTags(): array { return $this->tags; }
    public function setTags(array $tags): void { $this->tags = $this->normalizeTags($tags); }
    public function addTag(string $tag): void { $this->tags = $this->normalizeTags([...$this->tags, $tag]); }
    public function removeTag(string $tag): void
    {
        $needle = $this->normTag($tag);
        $this->tags = array_values(array_filter($this->tags, fn ($t) => $t !== $needle));
    }

    public function getMime(): string { return $this->mime; }
    public function setMime(string $mime): void { $this->mime = trim($mime) ?: 'application/octet-stream'; }

    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(?User $user): void { $this->owner = $user; }


    private function normalizeTags(array $tags): array
    {
        $tags = array_map([$this, 'normTag'], $tags);
        $tags = array_values(array_unique(array_filter($tags)));
        return $tags;
    }

    private function normTag(string $t): string
    {
        return mb_strtolower(trim($t));
    }
}