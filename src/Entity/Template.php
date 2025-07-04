<?php

namespace App\Entity;

use App\Repository\TemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TemplateRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Template
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'templates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Topic::class, inversedBy: 'templates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Topic $topic = null;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: Question::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: Form::class, cascade: ['remove'])]
    private Collection $forms;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'templates')]
    #[ORM\JoinTable(name: 'template_tags')]
    private Collection $tags;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: Comment::class, cascade: ['remove'])]
    private Collection $comments;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $likes = 0;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'template_liked_by')]
    private Collection $likedByUsers;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Choice(choices: ['public', 'restricted'])]
    private ?string $accessType = 'public';

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'template_restricted_access')]
    private Collection $restrictedUsers;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    #[ORM\Version]
    private ?int $version = 1;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->forms = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->likedByUsers = new ArrayCollection();
        $this->restrictedUsers = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTopic(): ?Topic
    {
        return $this->topic;
    }

    public function setTopic(?Topic $topic): static
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setTemplate($this);
            $this->reorderQuestions();
        }

        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getTemplate() === $this) {
                $question->setTemplate(null);
            }
            $this->reorderQuestions();
        }

        return $this;
    }

    private function reorderQuestions(): void
    {
        $position = 0;
        foreach ($this->questions as $question) {
            $question->setPosition($position++);
        }
    }

    /**
     * @return Collection<int, Form>
     */
    public function getForms(): Collection
    {
        return $this->forms;
    }

    public function addForm(Form $form): static
    {
        if (!$this->forms->contains($form)) {
            $this->forms->add($form);
            $form->setTemplate($this);
        }

        return $this;
    }

    public function removeForm(Form $form): static
    {
        if ($this->forms->removeElement($form)) {
            if ($form->getTemplate() === $this) {
                $form->setTemplate(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTemplate($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getTemplate() === $this) {
                $comment->setTemplate(null);
            }
        }

        return $this;
    }

    public function getLikes(): int
    {
        return $this->likes;
    }

    public function setLikes(int $likes): static
    {
        $this->likes = $likes;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getLikedByUsers(): Collection
    {
        return $this->likedByUsers;
    }

    public function addLikedByUser(User $likedByUser): static
    {
        if (!$this->likedByUsers->contains($likedByUser)) {
            $this->likedByUsers->add($likedByUser);
            $this->likes++;
        }

        return $this;
    }

    public function removeLikedByUser(User $likedByUser): static
    {
        if ($this->likedByUsers->removeElement($likedByUser)) {
            $this->likes--;
        }

        return $this;
    }

    public function getAccessType(): ?string
    {
        return $this->accessType;
    }

    public function setAccessType(string $accessType): static
    {
        $this->accessType = $accessType;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getRestrictedUsers(): Collection
    {
        return $this->restrictedUsers;
    }

    public function addRestrictedUser(User $restrictedUser): static
    {
        if (!$this->restrictedUsers->contains($restrictedUser)) {
            $this->restrictedUsers->add($restrictedUser);
        }

        return $this;
    }

    public function removeRestrictedUser(User $restrictedUser): static
    {
        $this->restrictedUsers->removeElement($restrictedUser);

        return $this;
    }

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