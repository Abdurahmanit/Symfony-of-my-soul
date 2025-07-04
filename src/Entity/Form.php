<?php

namespace App\Entity;

use App\Repository\FormRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormRepository::class)]
#[ORM\Table(name: '`form`')] // Using backticks as 'form' can be a reserved keyword
#[ORM\HasLifecycleCallbacks]
class Form
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Template::class, inversedBy: 'forms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Template $template = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'forms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $submittedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    // Fixed fields, invisible on template but shown on form:
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $fixedUserEmail = null; // Automatically filled
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fixedDate = null; // Automatically filled

    // Custom fields based on the Template's questions (up to 4 of each type)
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $stringAnswer1 = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $stringAnswer2 = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $stringAnswer3 = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $stringAnswer4 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textAnswer1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textAnswer2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textAnswer3 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textAnswer4 = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $intAnswer1 = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $intAnswer2 = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $intAnswer3 = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $intAnswer4 = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $checkboxAnswer1 = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $checkboxAnswer2 = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $checkboxAnswer3 = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $checkboxAnswer4 = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    #[ORM\Version]
    private ?int $version = 1;

    public function __construct()
    {
        $this->submittedAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->fixedDate = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        if ($user) {
            $this->fixedUserEmail = $user->getEmail();
        }

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeInterface
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeInterface $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFixedUserEmail(): ?string
    {
        return $this->fixedUserEmail;
    }

    public function setFixedUserEmail(?string $fixedUserEmail): static
    {
        $this->fixedUserEmail = $fixedUserEmail;

        return $this;
    }

    public function getFixedDate(): ?\DateTimeInterface
    {
        return $this->fixedDate;
    }

    public function setFixedDate(\DateTimeInterface $fixedDate): static
    {
        $this->fixedDate = $fixedDate;

        return $this;
    }

    // Getters and Setters for custom answers
    public function getStringAnswer1(): ?string { return $this->stringAnswer1; }
    public function setStringAnswer1(?string $stringAnswer1): static { $this->stringAnswer1 = $stringAnswer1; return $this; }
    public function getStringAnswer2(): ?string { return $this->stringAnswer2; }
    public function setStringAnswer2(?string $stringAnswer2): static { $this->stringAnswer2 = $stringAnswer2; return $this; }
    public function getStringAnswer3(): ?string { return $this->stringAnswer3; }
    public function setStringAnswer3(?string $stringAnswer3): static { $this->stringAnswer3 = $stringAnswer3; return $this; }
    public function getStringAnswer4(): ?string { return $this->stringAnswer4; }
    public function setStringAnswer4(?string $stringAnswer4): static { $this->stringAnswer4 = $stringAnswer4; return $this; }

    public function getTextAnswer1(): ?string { return $this->textAnswer1; }
    public function setTextAnswer1(?string $textAnswer1): static { $this->textAnswer1 = $textAnswer1; return $this; }
    public function getTextAnswer2(): ?string { return $this->textAnswer2; }
    public function setTextAnswer2(?string $textAnswer2): static { $this->textAnswer2 = $textAnswer2; return $this; }
    public function getTextAnswer3(): ?string { return $this->textAnswer3; }
    public function setTextAnswer3(?string $textAnswer3): static { $this->textAnswer3 = $textAnswer3; return $this; }
    public function getTextAnswer4(): ?string { return $this->textAnswer4; }
    public function setTextAnswer4(?string $textAnswer4): static { $this->textAnswer4 = $textAnswer4; return $this; }

    public function getIntAnswer1(): ?int { return $this->intAnswer1; }
    public function setIntAnswer1(?int $intAnswer1): static { $this->intAnswer1 = $intAnswer1; return $this; }
    public function getIntAnswer2(): ?int { return $this->intAnswer2; }
    public function setIntAnswer2(?int $intAnswer2): static { $this->intAnswer2 = $intAnswer2; return $this; }
    public function getIntAnswer3(): ?int { return $this->intAnswer3; }
    public function setIntAnswer3(?int $intAnswer3): static { $this->intAnswer3 = $intAnswer3; return $this; }
    public function getIntAnswer4(): ?int { return $this->intAnswer4; }
    public function setIntAnswer4(?int $intAnswer4): static { $this->intAnswer4 = $intAnswer4; return $this; }

    public function isCheckboxAnswer1(): ?bool { return $this->checkboxAnswer1; }
    public function setCheckboxAnswer1(?bool $checkboxAnswer1): static { $this->checkboxAnswer1 = $checkboxAnswer1; return $this; }
    public function isCheckboxAnswer2(): ?bool { return $this->checkboxAnswer2; }
    public function setCheckboxAnswer2(?bool $checkboxAnswer2): static { $this->checkboxAnswer2 = $checkboxAnswer2; return $this; }
    public function isCheckboxAnswer3(): ?bool { return $this->checkboxAnswer3; }
    public function setCheckboxAnswer3(?bool $checkboxAnswer3): static { $this->checkboxAnswer3 = $checkboxAnswer3; return $this; }
    public function isCheckboxAnswer4(): ?bool { return $this->checkboxAnswer4; }
    public function setCheckboxAnswer4(?bool $checkboxAnswer4): static { $this->checkboxAnswer4 = $checkboxAnswer4; return $this; }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }
}