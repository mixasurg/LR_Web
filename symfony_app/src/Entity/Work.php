<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WorkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkRepository::class)]
#[ORM\Table(name: 'work')]
#[ORM\UniqueConstraint(name: 'uniq_work_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Работа с таким slug уже существует.')]
class Work
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Название работы обязательно.')]
    private ?string $title = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Slug обязателен.')]
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        message: 'Slug должен состоять из латиницы, цифр и дефисов.'
    )]
    private ?string $slug = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Описание работы обязательно.')]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isPublished = true;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 1000, notInRangeMessage: 'Позиция должна быть от 0 до 1000.')]
    private int $sortOrder = 100;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, WorkPhoto>
     */
    #[ORM\OneToMany(mappedBy: 'work', targetEntity: WorkPhoto::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $photos;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = trim($title);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = mb_strtolower(trim($slug));

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = trim($description);

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

    /**
     * @return Collection<int, WorkPhoto>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    /**
     * @return list<WorkPhoto>
     */
    public function getPublishedPhotos(): array
    {
        $photos = array_filter($this->photos->toArray(), static fn (WorkPhoto $photo): bool => $photo->isPublished());
        usort($photos, static fn (WorkPhoto $a, WorkPhoto $b): int => [$a->getSortOrder(), $a->getId() ?? 0] <=> [$b->getSortOrder(), $b->getId() ?? 0]);

        return $photos;
    }

    public function getCoverPhoto(): ?WorkPhoto
    {
        $photos = $this->getPublishedPhotos();

        return $photos[0] ?? null;
    }

    public function getPhotoCount(): int
    {
        return count($this->getPublishedPhotos());
    }

    public function addPhoto(WorkPhoto $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setWork($this);
        }

        return $this;
    }

    public function removePhoto(WorkPhoto $photo): static
    {
        if ($this->photos->removeElement($photo) && $photo->getWork() === $this) {
            $photo->setWork(null);
        }

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
