<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\File(bucketName: 'images')]
#[ODM\Index(keys: ['uploadDate' => -1])]
#[ODM\Index(keys: ['metadata.owner' => 1, 'uploadDate' => -1])]
#[ODM\Index(keys: ['metadata.tags' => 1])]
class Image
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Filename]
    private ?string $name = null;

    #[ODM\UploadDate]
    private ?\DateTimeInterface $uploadDate = null;

    #[ODM\Metadata(targetDocument: ImageMetadata::class)]
    private ?ImageMetadata $metadata = null;

    public function __construct()
    {
        $this->metadata = new ImageMetadata();
    }

    public function getId(): ?string { 
        return $this->id;
    }

    public function getName(): ?string { 
        return $this->name;
    }

    public function getUploadDate(): ?\DateTimeInterface { 
        return $this->uploadDate;
    }



    public function getMetadata(): ImageMetadata
    {
        return $this->metadata ??= new ImageMetadata();
    }

    public function setMetadata(ImageMetadata $m): void { 
        $this->metadata = $m;
    }

    public function getTitle(): string { 
        return $this->getMetadata()->getTitle();
    }

    public function setTitle(string $t): void { 
        $this->getMetadata()->setTitle($t);
    }

    public function getTags(): array { 
        return $this->getMetadata()->getTags();
    }

    public function setTags(array $tags): void { 
        $this->getMetadata()->setTags($tags);
    }

    public function addTag(string $tag): void { 
        $this->getMetadata()->addTag($tag);
    }

    public function removeTag(string $tag): void { 
        $this->getMetadata()->removeTag($tag);
    }

    public function getMime(): string { 
        return $this->getMetadata()->getMime(); 
    }

    public function setMime(string $mime): void { 
        $this->getMetadata()->setMime($mime); 
    }

    public function getOwner(): ?User { 
        return $this->getMetadata()->getOwner(); 
    }

    public function setOwner(?User $u): void { 
        $this->getMetadata()->setOwner($u); 
    }

    public function __toString(): string
    {
        return $this->getTitle() ?: ($this->name ?? 'image');
    }
}
