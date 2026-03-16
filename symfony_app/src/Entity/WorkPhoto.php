<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WorkPhotoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkPhotoRepository::class)]
#[ORM\Table(name: 'work_photo')]
#[ORM\HasLifecycleCallbacks]
class WorkPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Work $work = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Путь к изображению обязателен.')]
    private ?string $imagePath = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $caption = null;

    #[ORM\Column]
    private bool $isPublished = true;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 1000, notInRangeMessage: 'Позиция должна быть от 0 до 1000.')]
    private int $sortOrder = 100;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWork(): ?Work
    {
        return $this->work;
    }

    public function setWork(?Work $work): static
    {
        $this->work = $work;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $imagePath): static
    {
        $this->imagePath = ltrim(trim($imagePath), '/');

        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = $caption !== null ? trim($caption) : null;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
