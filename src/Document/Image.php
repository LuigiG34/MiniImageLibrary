<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\File(bucketName: 'images')]
#[ODM\Index(keys: ['uploadDate' => -1])]
#[ODM\Index(keys: ['metadata.owner' => 1, 'uploadDate' => -1])]
#[ODM\Index(keys: ['metadata.tags' => 1])]
class Image
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\File\Filename]
    private ?string $filename = null;

    #[ODM\File\UploadDate]
    private ?\DateTimeInterface $uploadDate = null;

    #[ODM\File\Metadata(targetDocument: ImageMetadata::class)]
    private ?ImageMetadata $metadata = null;

    public function __construct()
    {
        $this->metadata = new ImageMetadata();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
    }

    public function getUploadDate(): ?\DateTimeInterface
    {
        return $this->uploadDate;
    }

    public function setUploadDate(?\DateTimeInterface $uploadDate): void
    {
        $this->uploadDate = $uploadDate;
    }

    public function getMetadata(): ?ImageMetadata
    {
        return $this->metadata ??= new ImageMetadata();
    }

    public function setMetadata(ImageMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getTitle(): string
    {
        return $this->getMetadata()->getTitle();
    }

    public function setTitle(string $title): void
    {
        $this->getMetadata()->setTitle($title);
    }

    public function getTags(): array
    {
        return $this->getMetadata()->getTags();
    }

    public function setTags(array $tags): void
    {
        $this->getMetadata()->setTags($tags);
    }

    public function addTag(string $tag): void
    {
        $this->getMetadata()->addTag($tag);
    }

    public function removeTag(string $tag): void
    {
        $this->getMetadata()->removeTag($tag);
    }

    public function getMime(): string
    {
        return $this->getMetadata()->getMime();
    }

    public function setMime(string $mime): void
    {
        $this->getMetadata()->setMime($mime);
    }

    public function getOwner(): ?User
    {
        return $this->getMetadata()->getOwner();
    }

    public function setOwner(?User $user): void
    {
        $this->getMetadata()->setOwner($user);
    }

    public function __toString(): string
    {
        return $this->getTitle() ?: ($this->filename ?? 'image');
    }
}