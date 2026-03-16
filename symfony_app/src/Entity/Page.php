<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'page')]
#[ORM\UniqueConstraint(name: 'uniq_page_slug', columns: ['slug'])]
#[ORM\UniqueConstraint(name: 'uniq_page_system_key', columns: ['system_key'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Страница с таким slug уже существует.')]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank(message: 'Название страницы обязательно.')]
    private ?string $title = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Slug обязателен.')]
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        message: 'Slug должен состоять из латиницы, цифр и дефисов.'
    )]
    private ?string $slug = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $systemKey = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $menuTitle = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Контент страницы обязателен.')]
    private ?string $content = null;

    #[ORM\Column]
    private bool $showInMenu = true;

    #[ORM\Column]
    private bool $isPublished = true;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 1000, notInRangeMessage: 'Позиция в меню должна быть от 0 до 1000.')]
    private int $position = 100;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getSystemKey(): ?string
    {
        return $this->systemKey;
    }

    public function setSystemKey(?string $systemKey): static
    {
        $this->systemKey = $systemKey !== null ? mb_strtolower(trim($systemKey)) : null;

        return $this;
    }

    public function getMenuTitle(): ?string
    {
        return $this->menuTitle;
    }

    public function setMenuTitle(?string $menuTitle): static
    {
        $this->menuTitle = $menuTitle !== null ? trim($menuTitle) : null;

        return $this;
    }

    public function getMenuLabel(): string
    {
        return $this->menuTitle ?: (string) $this->title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = trim($content);

        return $this;
    }

    public function isShowInMenu(): bool
    {
        return $this->showInMenu;
    }

    public function setShowInMenu(bool $showInMenu): static
    {
        $this->showInMenu = $showInMenu;

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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
